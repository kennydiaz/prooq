<?php

declare(strict_types=1);

use Prooq\Api\Db\Connection;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\App;

return function (App $app): void {
    $app->get('/api/downloads', function (ServerRequestInterface $req, ResponseInterface $res) {
        $country = $req->getQueryParams()['country'] ?? null;

        $sql = 'SELECT id, title, description, filename,
                       file_size_bytes AS fileSizeBytes,
                       mime_type AS mimeType,
                       country,
                       download_count AS downloadCount,
                       is_public AS isPublic
                FROM downloads
                WHERE is_public = 1';
        $bindings = [];

        if (is_string($country) && preg_match('/^[A-Z]{2}$/', $country) === 1) {
            $sql .= ' AND country = ?';
            $bindings[] = $country;
        }

        $sql .= ' ORDER BY created_at DESC';

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

    // TODO Fase 4: GET /api/downloads/{id}/file — proxy de descarga (lógica que hoy vive en dl.php).
    // Validar is_public, leer archivo de /downloads/, incrementar download_count, devolver con Content-Disposition.
};
