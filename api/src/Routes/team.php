<?php

declare(strict_types=1);

use Prooq\Api\Db\Connection;
use Prooq\Api\Middleware\AdminAuth;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;
use Slim\App;

return function (App $app): void {
    // GET /api/team?country=US — equipo publico de una sucursal (solo activos).
    $app->get('/api/team', function (ServerRequestInterface $req, ResponseInterface $res) {
        $country = $req->getQueryParams()['country'] ?? null;

        $sql = 'SELECT m.id, m.name, m.role, m.photo, m.bio, m.email,
                       c.country, c.display_order AS displayOrder
                FROM team_members m
                JOIN team_member_countries c ON c.member_id = m.id
                WHERE m.is_active = 1';
        $bind = [];
        if (is_string($country) && preg_match('/^[A-Z]{2}$/', $country) === 1) {
            $sql .= ' AND c.country = ?';
            $bind[] = $country;
        }
        $sql .= ' ORDER BY c.display_order ASC, m.name ASC';

        $stmt = Connection::get()->prepare($sql);
        $stmt->execute($bind);
        $rows = array_map(static function (array $r): array {
            return [
                'id'           => (int) $r['id'],
                'name'         => $r['name'],
                'role'         => $r['role'],
                'bio'          => $r['bio'],
                'email'        => $r['email'],
                'country'      => $r['country'],
                'displayOrder' => (int) $r['displayOrder'],
                'photoUrl'     => $r['photo'] ? '/uploads/team/' . $r['photo'] : null,
            ];
        }, $stmt->fetchAll());

        $res->getBody()->write(json_encode($rows) ?: '[]');
        return $res->withHeader('Content-Type', 'application/json');
    });

    // GET /api/admin/team — todos los miembros con su lista de paises.
    $app->get('/api/admin/team', function (ServerRequestInterface $req, ResponseInterface $res) {
        $pdo = Connection::get();
        $members = $pdo->query(
            'SELECT id, name, role, photo, bio, email, is_active AS isActive, created_at AS createdAt
             FROM team_members ORDER BY name ASC'
        )->fetchAll();

        $links = $pdo->query(
            'SELECT member_id, country, display_order AS displayOrder
             FROM team_member_countries ORDER BY display_order ASC'
        )->fetchAll();

        $byMember = [];
        foreach ($links as $l) {
            $byMember[(int) $l['member_id']][] = ['country' => $l['country'], 'displayOrder' => (int) $l['displayOrder']];
        }

        $out = array_map(static function (array $m) use ($byMember): array {
            $id = (int) $m['id'];
            return [
                'id'        => $id,
                'name'      => $m['name'],
                'role'      => $m['role'],
                'bio'       => $m['bio'],
                'email'     => $m['email'],
                'isActive'  => (bool) $m['isActive'],
                'createdAt' => $m['createdAt'],
                'photoUrl'  => $m['photo'] ? '/uploads/team/' . $m['photo'] : null,
                'countries' => array_map(static fn (array $c): string => $c['country'], $byMember[$id] ?? []),
            ];
        }, $members);

        $res->getBody()->write(json_encode($out) ?: '[]');
        return $res->withHeader('Content-Type', 'application/json');
    })->add(new AdminAuth());

    // POST /api/admin/team — crear miembro (multipart: name, role, bio, email,
    // countries="US,PA", active, photo opcional).
    $app->post('/api/admin/team', function (ServerRequestInterface $req, ResponseInterface $res) {
        $body = (array) $req->getParsedBody();
        $name = is_string($body['name'] ?? null) ? trim((string) $body['name']) : '';
        if ($name === '') {
            return team_json($res, ['error' => 'missing_name'], 400);
        }

        $photo = team_store_photo($req->getUploadedFiles()['photo'] ?? null, $err);
        if ($err !== null) {
            return team_json($res, ['error' => $err], 400);
        }

        $pdo = Connection::get();
        $stmt = $pdo->prepare(
            'INSERT INTO team_members (name, role, photo, bio, email, is_active)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $name,
            team_str($body['role'] ?? null),
            $photo,
            team_str($body['bio'] ?? null),
            team_str($body['email'] ?? null),
            team_active($body['active'] ?? '1'),
        ]);
        $id = (int) $pdo->lastInsertId();
        team_set_countries($pdo, $id, $body['countries'] ?? '');

        return team_json($res, ['ok' => true, 'id' => $id], 201);
    })->add(new AdminAuth());

    // POST /api/admin/team/{id} — actualizar (POST por el multipart de la foto).
    $app->post('/api/admin/team/{id}', function (ServerRequestInterface $req, ResponseInterface $res, array $args) {
        $id = (int) ($args['id'] ?? 0);
        if ($id <= 0) {
            return team_json($res, ['error' => 'invalid_id'], 400);
        }
        $pdo = Connection::get();
        $cur = $pdo->prepare('SELECT photo FROM team_members WHERE id = ?');
        $cur->execute([$id]);
        $row = $cur->fetch();
        if (!$row) {
            return team_json($res, ['error' => 'not_found'], 404);
        }

        $body = (array) $req->getParsedBody();
        $name = is_string($body['name'] ?? null) ? trim((string) $body['name']) : '';
        if ($name === '') {
            return team_json($res, ['error' => 'missing_name'], 400);
        }

        // Foto: si suben una nueva, reemplaza (y borra la vieja); si no, conserva.
        $newPhoto = team_store_photo($req->getUploadedFiles()['photo'] ?? null, $err);
        if ($err !== null) {
            return team_json($res, ['error' => $err], 400);
        }
        $photo = $row['photo'];
        if ($newPhoto !== null) {
            if (is_string($photo) && $photo !== '') {
                $old = __DIR__ . '/../../public/uploads/team/' . basename($photo);
                if (is_file($old)) {
                    @unlink($old);
                }
            }
            $photo = $newPhoto;
        }

        $pdo->prepare(
            'UPDATE team_members SET name = ?, role = ?, photo = ?, bio = ?, email = ?, is_active = ? WHERE id = ?'
        )->execute([
            $name,
            team_str($body['role'] ?? null),
            $photo,
            team_str($body['bio'] ?? null),
            team_str($body['email'] ?? null),
            team_active($body['active'] ?? '1'),
            $id,
        ]);
        team_set_countries($pdo, $id, $body['countries'] ?? '');

        return team_json($res, ['ok' => true, 'id' => $id], 200);
    })->add(new AdminAuth());

    // DELETE /api/admin/team/{id}
    $app->delete('/api/admin/team/{id}', function (ServerRequestInterface $req, ResponseInterface $res, array $args) {
        $id = (int) ($args['id'] ?? 0);
        if ($id <= 0) {
            return team_json($res, ['error' => 'invalid_id'], 400);
        }
        $pdo = Connection::get();
        $cur = $pdo->prepare('SELECT photo FROM team_members WHERE id = ?');
        $cur->execute([$id]);
        $row = $cur->fetch();
        if (!$row) {
            return team_json($res, ['error' => 'not_found'], 404);
        }
        if (is_string($row['photo']) && $row['photo'] !== '') {
            $file = __DIR__ . '/../../public/uploads/team/' . basename($row['photo']);
            if (is_file($file)) {
                @unlink($file);
            }
        }
        // ON DELETE CASCADE limpia team_member_countries.
        $pdo->prepare('DELETE FROM team_members WHERE id = ?')->execute([$id]);

        return team_json($res, ['ok' => true], 200);
    })->add(new AdminAuth());
};

function team_json(ResponseInterface $res, array $data, int $status): ResponseInterface
{
    $res->getBody()->write(json_encode($data) ?: '{}');
    return $res->withHeader('Content-Type', 'application/json')->withStatus($status);
}

function team_str(mixed $v): ?string
{
    if (!is_string($v)) {
        return null;
    }
    $v = trim($v);
    return $v === '' ? null : $v;
}

function team_active(mixed $v): int
{
    return in_array($v, ['0', 'false', '', 0, false, null], true) ? 0 : 1;
}

/** Reemplaza las sucursales de un miembro a partir de un CSV "US,PA,ES". */
function team_set_countries(PDO $pdo, int $memberId, mixed $csv): void
{
    $pdo->prepare('DELETE FROM team_member_countries WHERE member_id = ?')->execute([$memberId]);
    if (!is_string($csv) || $csv === '') {
        return;
    }
    $ins = $pdo->prepare(
        'INSERT INTO team_member_countries (member_id, country, display_order) VALUES (?, ?, ?)'
    );
    $order = 0;
    $seen = [];
    foreach (explode(',', $csv) as $raw) {
        $cc = strtoupper(trim($raw));
        if (preg_match('/^[A-Z]{2}$/', $cc) === 1 && !isset($seen[$cc])) {
            $seen[$cc] = true;
            $ins->execute([$memberId, $cc, $order++]);
        }
    }
}

/**
 * Guarda la foto subida en uploads/team/ y devuelve el filename, o null si no
 * se subio archivo. $err se setea con un codigo de error si la validacion falla.
 */
function team_store_photo(?UploadedFileInterface $file, ?string &$err = null): ?string
{
    $err = null;
    if (!$file instanceof UploadedFileInterface || $file->getError() === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($file->getError() !== UPLOAD_ERR_OK) {
        $err = 'upload_error_' . $file->getError();
        return null;
    }
    $size = $file->getSize();
    if ($size === null || $size > 5 * 1024 * 1024) {
        $err = 'file_too_large_max_5mb';
        return null;
    }
    $mime = $file->getClientMediaType() ?? '';
    $map = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    if (!isset($map[$mime])) {
        $err = 'invalid_mime_type';
        return null;
    }
    $filename = bin2hex(random_bytes(16)) . '.' . $map[$mime];
    $dir = __DIR__ . '/../../public/uploads/team';
    if (!is_dir($dir)) {
        mkdir($dir, 0o755, true);
    }
    $file->moveTo($dir . '/' . $filename);
    return $filename;
}
