<?php

declare(strict_types=1);

use Prooq\Api\Db\Connection;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
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
};

function jsonError(ResponseInterface $res, string $code, int $status): ResponseInterface {
    $res->getBody()->write(json_encode(['error' => $code]) ?: '{}');
    return $res->withHeader('Content-Type', 'application/json')->withStatus($status);
}
