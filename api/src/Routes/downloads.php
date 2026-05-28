<?php

declare(strict_types=1);

use Prooq\Api\Db\Connection;
use Prooq\Api\Middleware\AdminAuth;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;
use Slim\App;
use Slim\Psr7\Stream;

return function (App $app): void {
    $app->get('/api/downloads', function (ServerRequestInterface $req, ResponseInterface $res) {
        $country = $req->getQueryParams()['country'] ?? null;

        $sql = 'SELECT id, title, description, filename,
                       file_size_bytes AS fileSizeBytes,
                       mime_type       AS mimeType,
                       external_url    AS externalUrl,
                       icon_url        AS iconUrl,
                       country,
                       slug,
                       download_count  AS downloadCount,
                       is_public       AS isPublic
                FROM downloads
                WHERE is_public = 1';
        $bindings = [];

        // Rows con country = NULL son "globales". Filter de country devuelve
        // los del pais + los globales (NULL).
        if (is_string($country) && preg_match('/^[A-Z]{2}$/', $country) === 1) {
            $sql .= ' AND (country = ? OR country IS NULL)';
            $bindings[] = $country;
        }

        $sql .= ' ORDER BY id ASC';

        $stmt = Connection::get()->prepare($sql);
        $stmt->execute($bindings);
        $rows = $stmt->fetchAll();

        foreach ($rows as &$row) {
            $row['isPublic'] = (bool) $row['isPublic'];
            $row['downloadCount'] = (int) $row['downloadCount'];
            $row['fileSizeBytes'] = $row['fileSizeBytes'] !== null ? (int) $row['fileSizeBytes'] : null;
        }

        $res->getBody()->write(json_encode($rows) ?: '[]');
        return $res->withHeader('Content-Type', 'application/json');
    });

    // GET /api/downloads/{idOrSlug}/file — proxy de descarga.
    // Acepta numeric id (preferido) o slug. Valida is_public, lee del directorio
    // privado api/downloads/ (sibling de public/, fuera del docroot), incrementa
    // download_count y devuelve el binario con Content-Disposition: attachment.
    $app->get('/api/downloads/{idOrSlug}/file', function (
        ServerRequestInterface $req,
        ResponseInterface $res,
        array $args,
    ) {
        $idOrSlug = $args['idOrSlug'] ?? '';
        if ($idOrSlug === '') {
            return jsonError($res, 'missing_id', 400);
        }

        $pdo = Connection::get();
        if (ctype_digit((string) $idOrSlug)) {
            $stmt = $pdo->prepare(
                'SELECT id, filename, mime_type FROM downloads
                 WHERE id = ? AND is_public = 1 LIMIT 1'
            );
            $stmt->execute([(int) $idOrSlug]);
        } else {
            $stmt = $pdo->prepare(
                'SELECT id, filename, mime_type FROM downloads
                 WHERE slug = ? AND is_public = 1 LIMIT 1'
            );
            $stmt->execute([(string) $idOrSlug]);
        }

        $row = $stmt->fetch();
        if (!$row) {
            return jsonError($res, 'not_found', 404);
        }
        if (empty($row['filename'])) {
            return jsonError($res, 'no_file_attached', 404);
        }

        // Storage privado: api/downloads/ (un dir arriba del docroot public/).
        // __DIR__ es api/src/Routes, asi que /../../downloads = api/downloads.
        $filePath = __DIR__ . '/../../downloads/' . basename((string) $row['filename']);
        if (!is_file($filePath) || !is_readable($filePath)) {
            error_log('downloads/file: file not found on disk: ' . $filePath);
            return jsonError($res, 'file_missing_on_disk', 410);
        }

        // Incrementa contador (no bloqueante — si falla, no aborta la descarga).
        try {
            $pdo->prepare('UPDATE downloads SET download_count = download_count + 1 WHERE id = ?')
                ->execute([$row['id']]);
        } catch (\Throwable $e) {
            error_log('downloads/file: count increment failed: ' . $e->getMessage());
        }

        $size = filesize($filePath);
        $mime = (string) ($row['mime_type'] ?? '') ?: 'application/octet-stream';

        // Stream el archivo — Slim Psr7 Stream lo lee chunk por chunk sin cargar
        // todo en memoria (importante para iVMS-4200 que pesa 332 MB).
        $fh = fopen($filePath, 'rb');
        if ($fh === false) {
            return jsonError($res, 'cannot_open_file', 500);
        }

        return $res
            ->withHeader('Content-Type', $mime)
            ->withHeader('Content-Length', (string) ($size !== false ? $size : 0))
            ->withHeader('Content-Disposition', 'attachment; filename="' . basename((string) $row['filename']) . '"')
            ->withHeader('Cache-Control', 'public, max-age=3600')
            ->withBody(new Stream($fh));
    });

    // ── Admin CRUD (bajo sesion) ─────────────────────────────────────────────

    // GET /api/admin/downloads — listado completo (todos los paises, incl. non-public).
    $app->get('/api/admin/downloads', function (ServerRequestInterface $req, ResponseInterface $res) {
        $stmt = Connection::get()->query(
            'SELECT id, title, description, filename,
                    file_size_bytes AS fileSizeBytes,
                    mime_type       AS mimeType,
                    external_url    AS externalUrl,
                    icon_url        AS iconUrl,
                    country, slug,
                    download_count  AS downloadCount,
                    is_public       AS isPublic,
                    created_at      AS createdAt
             FROM downloads
             ORDER BY (country IS NULL) ASC, country ASC, id ASC'
        );
        $rows = $stmt->fetchAll();
        foreach ($rows as &$row) {
            $row['isPublic'] = (bool) $row['isPublic'];
            $row['downloadCount'] = (int) $row['downloadCount'];
            $row['fileSizeBytes'] = $row['fileSizeBytes'] !== null ? (int) $row['fileSizeBytes'] : null;
        }
        $res->getBody()->write(json_encode($rows) ?: '[]');
        return $res->withHeader('Content-Type', 'application/json');
    })->add(new AdminAuth());

    // POST /api/admin/downloads — crear (multipart: title, description, country,
    // slug, external_url, is_public + opcional icon (imagen) + file (binario)).
    $app->post('/api/admin/downloads', function (ServerRequestInterface $req, ResponseInterface $res) {
        $body = (array) $req->getParsedBody();
        $files = $req->getUploadedFiles();

        // post_max_size excedido: PHP descarta body y files aunque haya payload.
        if ((int) $req->getHeaderLine('Content-Length') > 0 && $body === [] && $files === []) {
            return jsonError($res, 'upload_exceeds_server_limit', 413);
        }

        $title = is_string($body['title'] ?? null) ? trim((string) $body['title']) : '';
        if ($title === '') {
            return jsonError($res, 'missing_title', 400);
        }

        $fields = dl_validate_fields($body, $err);
        if ($err !== null) {
            return jsonError($res, $err, 400);
        }

        $iconUrl = null;
        $iconName = dl_store_image($files['icon'] ?? null, $err);
        if ($err !== null) {
            return jsonError($res, $err, 400);
        }
        if ($iconName !== null) {
            $iconUrl = '/uploads/downloads/' . $iconName;
        }

        $bin = dl_store_binary($files['file'] ?? null, $err);
        if ($err !== null) {
            return jsonError($res, $err, 400);
        }

        $pdo = Connection::get();
        try {
            $stmt = $pdo->prepare(
                'INSERT INTO downloads
                    (title, description, filename, file_size_bytes, mime_type,
                     external_url, icon_url, country, slug, is_public)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                $title,
                dl_str($body['description'] ?? null),
                $bin['filename'] ?? null,
                $bin['size'] ?? null,
                $bin['mime'] ?? null,
                $fields['externalUrl'],
                $iconUrl,
                $fields['country'],
                $fields['slug'],
                dl_bool($body['is_public'] ?? '1'),
            ]);
        } catch (\PDOException $e) {
            if ($e->getCode() === '23000') {
                return jsonError($res, 'slug_taken', 409);
            }
            throw $e;
        }

        $res->getBody()->write(json_encode(['ok' => true, 'id' => (int) $pdo->lastInsertId()]) ?: '{}');
        return $res->withHeader('Content-Type', 'application/json')->withStatus(201);
    })->add(new AdminAuth());

    // POST /api/admin/downloads/{id} — actualizar (POST por el multipart).
    $app->post('/api/admin/downloads/{id}', function (ServerRequestInterface $req, ResponseInterface $res, array $args) {
        $id = (int) ($args['id'] ?? 0);
        if ($id <= 0) {
            return jsonError($res, 'invalid_id', 400);
        }

        $body = (array) $req->getParsedBody();
        $files = $req->getUploadedFiles();
        if ((int) $req->getHeaderLine('Content-Length') > 0 && $body === [] && $files === []) {
            return jsonError($res, 'upload_exceeds_server_limit', 413);
        }

        $pdo = Connection::get();
        $cur = $pdo->prepare('SELECT icon_url, filename FROM downloads WHERE id = ?');
        $cur->execute([$id]);
        $row = $cur->fetch();
        if (!$row) {
            return jsonError($res, 'not_found', 404);
        }

        $title = is_string($body['title'] ?? null) ? trim((string) $body['title']) : '';
        if ($title === '') {
            return jsonError($res, 'missing_title', 400);
        }

        $fields = dl_validate_fields($body, $err);
        if ($err !== null) {
            return jsonError($res, $err, 400);
        }

        // Icono: si suben uno nuevo, reemplaza (y borra el viejo SOLO si era subido).
        $iconUrl = $row['icon_url'];
        $newIcon = dl_store_image($files['icon'] ?? null, $err);
        if ($err !== null) {
            return jsonError($res, $err, 400);
        }
        if ($newIcon !== null) {
            if (is_string($iconUrl) && str_starts_with($iconUrl, '/uploads/downloads/')) {
                $old = __DIR__ . '/../../public/uploads/downloads/' . basename($iconUrl);
                if (is_file($old)) {
                    @unlink($old);
                }
            }
            $iconUrl = '/uploads/downloads/' . $newIcon;
        }

        // Binario: idem; conserva el actual si no suben uno nuevo.
        $bin = dl_store_binary($files['file'] ?? null, $err);
        if ($err !== null) {
            return jsonError($res, $err, 400);
        }

        try {
            if ($bin !== null) {
                if (is_string($row['filename']) && $row['filename'] !== '') {
                    $old = __DIR__ . '/../../downloads/' . basename((string) $row['filename']);
                    if (is_file($old)) {
                        @unlink($old);
                    }
                }
                $pdo->prepare(
                    'UPDATE downloads SET title = ?, description = ?, filename = ?, file_size_bytes = ?,
                            mime_type = ?, external_url = ?, icon_url = ?, country = ?, slug = ?, is_public = ?
                     WHERE id = ?'
                )->execute([
                    $title,
                    dl_str($body['description'] ?? null),
                    $bin['filename'],
                    $bin['size'],
                    $bin['mime'],
                    $fields['externalUrl'],
                    $iconUrl,
                    $fields['country'],
                    $fields['slug'],
                    dl_bool($body['is_public'] ?? '1'),
                    $id,
                ]);
            } else {
                $pdo->prepare(
                    'UPDATE downloads SET title = ?, description = ?, external_url = ?, icon_url = ?,
                            country = ?, slug = ?, is_public = ?
                     WHERE id = ?'
                )->execute([
                    $title,
                    dl_str($body['description'] ?? null),
                    $fields['externalUrl'],
                    $iconUrl,
                    $fields['country'],
                    $fields['slug'],
                    dl_bool($body['is_public'] ?? '1'),
                    $id,
                ]);
            }
        } catch (\PDOException $e) {
            if ($e->getCode() === '23000') {
                return jsonError($res, 'slug_taken', 409);
            }
            throw $e;
        }

        $res->getBody()->write(json_encode(['ok' => true, 'id' => $id]) ?: '{}');
        return $res->withHeader('Content-Type', 'application/json');
    })->add(new AdminAuth());

    // DELETE /api/admin/downloads/{id}
    $app->delete('/api/admin/downloads/{id}', function (ServerRequestInterface $req, ResponseInterface $res, array $args) {
        $id = (int) ($args['id'] ?? 0);
        if ($id <= 0) {
            return jsonError($res, 'invalid_id', 400);
        }
        $pdo = Connection::get();
        $cur = $pdo->prepare('SELECT icon_url, filename FROM downloads WHERE id = ?');
        $cur->execute([$id]);
        $row = $cur->fetch();
        if (!$row) {
            return jsonError($res, 'not_found', 404);
        }

        if (is_string($row['icon_url']) && str_starts_with($row['icon_url'], '/uploads/downloads/')) {
            $f = __DIR__ . '/../../public/uploads/downloads/' . basename($row['icon_url']);
            if (is_file($f)) {
                @unlink($f);
            }
        }
        if (is_string($row['filename']) && $row['filename'] !== '') {
            $f = __DIR__ . '/../../downloads/' . basename((string) $row['filename']);
            if (is_file($f)) {
                @unlink($f);
            }
        }

        $pdo->prepare('DELETE FROM downloads WHERE id = ?')->execute([$id]);

        $res->getBody()->write(json_encode(['ok' => true]) ?: '{}');
        return $res->withHeader('Content-Type', 'application/json');
    })->add(new AdminAuth());
};

function jsonError(ResponseInterface $res, string $code, int $status): ResponseInterface {
    $res->getBody()->write(json_encode(['error' => $code]) ?: '{}');
    return $res->withHeader('Content-Type', 'application/json')->withStatus($status);
}

function dl_str(mixed $v): ?string
{
    if (!is_string($v)) {
        return null;
    }
    $v = trim($v);
    return $v === '' ? null : $v;
}

function dl_bool(mixed $v): int
{
    return in_array($v, ['0', 'false', '', 0, false, null], true) ? 0 : 1;
}

/**
 * Valida country (ISO-2), slug ([a-z0-9-]) y external_url (http/https).
 * Devuelve ['country'=>?, 'slug'=>?, 'externalUrl'=>?]; setea $err con un codigo.
 */
function dl_validate_fields(array $body, ?string &$err = null): array
{
    $err = null;
    $country = null;
    if (isset($body['country']) && is_string($body['country']) && $body['country'] !== '') {
        if (preg_match('/^[A-Z]{2}$/', $body['country']) !== 1) {
            $err = 'invalid_country';
            return [];
        }
        $country = $body['country'];
    }

    $slug = null;
    if (isset($body['slug']) && is_string($body['slug']) && trim($body['slug']) !== '') {
        $s = trim($body['slug']);
        if (preg_match('/^[a-z0-9-]+$/', $s) !== 1) {
            $err = 'invalid_slug';
            return [];
        }
        $slug = $s;
    }

    $externalUrl = null;
    if (isset($body['external_url']) && is_string($body['external_url']) && trim($body['external_url']) !== '') {
        $u = trim($body['external_url']);
        if (preg_match('#^https?://#i', $u) !== 1) {
            $err = 'invalid_external_url';
            return [];
        }
        $externalUrl = $u;
    }

    return ['country' => $country, 'slug' => $slug, 'externalUrl' => $externalUrl];
}

/**
 * Guarda un icono (imagen) en public/uploads/downloads/ y devuelve el filename,
 * o null si no se subio archivo. $err se setea con un codigo si falla.
 */
function dl_store_image(?UploadedFileInterface $file, ?string &$err = null): ?string
{
    $err = null;
    if (!$file instanceof UploadedFileInterface || $file->getError() === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($file->getError() !== UPLOAD_ERR_OK) {
        $err = 'icon_upload_error_' . $file->getError();
        return null;
    }
    $size = $file->getSize();
    if ($size === null || $size > 5 * 1024 * 1024) {
        $err = 'icon_too_large_max_5mb';
        return null;
    }
    $mime = $file->getClientMediaType() ?? '';
    $map = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
        'image/svg+xml' => 'svg',
    ];
    if (!isset($map[$mime])) {
        $err = 'icon_invalid_mime_type';
        return null;
    }
    $filename = bin2hex(random_bytes(16)) . '.' . $map[$mime];
    $dir = __DIR__ . '/../../public/uploads/downloads';
    if (!is_dir($dir)) {
        mkdir($dir, 0o755, true);
    }
    $file->moveTo($dir . '/' . $filename);
    return $filename;
}

/**
 * Guarda el binario en api/downloads/ (privado). Devuelve
 * ['filename'=>string,'size'=>int,'mime'=>string] o null si no se subio.
 */
function dl_store_binary(?UploadedFileInterface $file, ?string &$err = null): ?array
{
    $err = null;
    if (!$file instanceof UploadedFileInterface || $file->getError() === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($file->getError() !== UPLOAD_ERR_OK) {
        $err = 'file_upload_error_' . $file->getError();
        return null;
    }
    $size = $file->getSize();
    if ($size === null || $size > 700 * 1024 * 1024) {
        $err = 'file_too_large_max_700mb';
        return null;
    }
    $clientName = $file->getClientFilename() ?? 'download.bin';
    $ext = strtolower(pathinfo($clientName, PATHINFO_EXTENSION));
    $allowed = ['exe', 'msi', 'zip', 'rar', '7z', 'dmg', 'pkg', 'apk', 'deb', 'iso'];
    if (!in_array($ext, $allowed, true)) {
        $err = 'file_invalid_extension';
        return null;
    }

    // Nombre legible (= nombre con que se descarga); sanitizado y sin colisiones.
    $safe = preg_replace('/[^A-Za-z0-9._-]/', '_', basename($clientName)) ?? '';
    if ($safe === '' || $safe === '.' || $safe === '..') {
        $safe = bin2hex(random_bytes(8)) . '.' . $ext;
    }
    $dir = __DIR__ . '/../../downloads';
    if (!is_dir($dir)) {
        mkdir($dir, 0o755, true);
    }
    if (file_exists($dir . '/' . $safe)) {
        $safe = bin2hex(random_bytes(4)) . '-' . $safe;
    }
    $file->moveTo($dir . '/' . $safe);

    $mime = $file->getClientMediaType();
    return [
        'filename' => $safe,
        'size' => $size,
        'mime' => is_string($mime) && $mime !== '' ? $mime : 'application/octet-stream',
    ];
}
