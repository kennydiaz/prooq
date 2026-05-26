<?php

declare(strict_types=1);

use Prooq\Api\Auth\Session;
use Prooq\Api\Db\Connection;
use Prooq\Api\Middleware\AdminAuth;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;
use Slim\App;

return function (App $app): void {
    // GET /api/gallery — listado público con filter de country
    $app->get('/api/gallery', function (ServerRequestInterface $req, ResponseInterface $res) {
        $country = $req->getQueryParams()['country'] ?? null;

        $sql = 'SELECT id, title, alt, caption, filename, mime_type,
                       file_size_bytes AS fileSizeBytes,
                       width_px        AS widthPx,
                       height_px       AS heightPx,
                       country,
                       display_order   AS displayOrder,
                       is_public       AS isPublic,
                       created_at      AS createdAt
                FROM gallery_items
                WHERE is_public = 1';
        $bindings = [];
        if (is_string($country) && preg_match('/^[A-Z]{2}$/', $country) === 1) {
            $sql .= ' AND (country = ? OR country IS NULL)';
            $bindings[] = $country;
        }
        $sql .= ' ORDER BY display_order ASC, created_at DESC';

        $stmt = Connection::get()->prepare($sql);
        $stmt->execute($bindings);
        $rows = $stmt->fetchAll();

        foreach ($rows as &$row) {
            $row['isPublic'] = (bool) $row['isPublic'];
            $row['displayOrder'] = (int) $row['displayOrder'];
            $row['fileSizeBytes'] = $row['fileSizeBytes'] !== null ? (int) $row['fileSizeBytes'] : null;
            $row['widthPx'] = $row['widthPx'] !== null ? (int) $row['widthPx'] : null;
            $row['heightPx'] = $row['heightPx'] !== null ? (int) $row['heightPx'] : null;
            // URL pública: api.prooq.com/uploads/gallery/{filename}
            $row['url'] = '/uploads/gallery/' . $row['filename'];
        }

        $res->getBody()->write(json_encode($rows) ?: '[]');
        return $res->withHeader('Content-Type', 'application/json');
    });

    // POST /api/admin/gallery/upload — multipart con campo 'image' + opcional title/alt/country
    $app->post('/api/admin/gallery/upload', function (ServerRequestInterface $req, ResponseInterface $res) {
        $files = $req->getUploadedFiles();
        /** @var UploadedFileInterface|null $image */
        $image = $files['image'] ?? null;

        if (!$image instanceof UploadedFileInterface) {
            return jsonError($res, 'missing_image', 400);
        }
        if ($image->getError() !== UPLOAD_ERR_OK) {
            return jsonError($res, 'upload_error_' . $image->getError(), 400);
        }

        $size = $image->getSize();
        if ($size === null || $size > 10 * 1024 * 1024) {
            return jsonError($res, 'file_too_large_max_10mb', 413);
        }

        $clientName = $image->getClientFilename() ?? 'upload';
        $clientMime = $image->getClientMediaType() ?? '';
        $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        if (!in_array($clientMime, $allowed, true)) {
            return jsonError($res, 'invalid_mime_type', 415);
        }

        $ext = strtolower(pathinfo($clientName, PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true)) {
            $ext = match ($clientMime) {
                'image/jpeg' => 'jpg',
                'image/png'  => 'png',
                'image/webp' => 'webp',
                'image/gif'  => 'gif',
                default      => 'bin',
            };
        }

        // UUID-like filename para evitar guessability + colisiones
        $uniqueName = bin2hex(random_bytes(16)) . '.' . $ext;

        // __DIR__ = api/src/Routes. Storage en api/public/uploads/gallery/
        $storageDir = __DIR__ . '/../../public/uploads/gallery';
        if (!is_dir($storageDir)) {
            mkdir($storageDir, 0o755, true);
        }
        $destPath = $storageDir . '/' . $uniqueName;
        $image->moveTo($destPath);

        // Dimensiones (opcional, no aborta si falla)
        $width = null;
        $height = null;
        $info = @getimagesize($destPath);
        if (is_array($info)) {
            $width = (int) $info[0];
            $height = (int) $info[1];
        }

        $body = (array) $req->getParsedBody();
        $title   = is_string($body['title'] ?? null)   ? trim((string) $body['title']) : null;
        $alt     = is_string($body['alt'] ?? null)     ? trim((string) $body['alt']) : null;
        $caption = is_string($body['caption'] ?? null) ? trim((string) $body['caption']) : null;
        $country = null;
        if (isset($body['country']) && is_string($body['country']) && preg_match('/^[A-Z]{2}$/', $body['country']) === 1) {
            $country = $body['country'];
        }
        $displayOrder = isset($body['display_order']) && is_numeric($body['display_order'])
            ? (int) $body['display_order']
            : 0;

        $pdo = Connection::get();
        $stmt = $pdo->prepare(
            'INSERT INTO gallery_items
                (title, alt, caption, filename, mime_type, file_size_bytes, width_px, height_px,
                 country, display_order, is_public, uploaded_by)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?)'
        );
        $stmt->execute([
            $title ?: null,
            $alt ?: null,
            $caption ?: null,
            $uniqueName,
            $clientMime,
            $size,
            $width,
            $height,
            $country,
            $displayOrder,
            Session::currentUserId(),
        ]);

        $payload = [
            'ok' => true,
            'id' => (int) $pdo->lastInsertId(),
            'filename' => $uniqueName,
            'url' => '/uploads/gallery/' . $uniqueName,
            'mime_type' => $clientMime,
            'file_size_bytes' => $size,
            'width_px' => $width,
            'height_px' => $height,
        ];
        $res->getBody()->write(json_encode($payload) ?: '{}');
        return $res->withHeader('Content-Type', 'application/json')->withStatus(201);
    })->add(new AdminAuth());

    // DELETE /api/admin/gallery/{id}
    $app->delete('/api/admin/gallery/{id}', function (
        ServerRequestInterface $req,
        ResponseInterface $res,
        array $args,
    ) {
        $id = (int) ($args['id'] ?? 0);
        if ($id <= 0) {
            return jsonError($res, 'invalid_id', 400);
        }

        $pdo = Connection::get();
        $stmt = $pdo->prepare('SELECT filename FROM gallery_items WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if (!$row) {
            return jsonError($res, 'not_found', 404);
        }

        $filePath = __DIR__ . '/../../public/uploads/gallery/' . basename((string) $row['filename']);
        if (is_file($filePath)) {
            @unlink($filePath);
        }

        $pdo->prepare('DELETE FROM gallery_items WHERE id = ?')->execute([$id]);

        $res->getBody()->write(json_encode(['ok' => true]) ?: '{}');
        return $res->withHeader('Content-Type', 'application/json');
    })->add(new AdminAuth());
};
