<?php

declare(strict_types=1);

use Prooq\Api\Db\Connection;
use Prooq\Api\Middleware\AdminAuth;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;
use Slim\App;

// Blog gestionado desde el admin (CMS en BD). Lectura publica por sucursal
// (country) para que cada app construya su /blog en build time; CRUD protegido
// con sesion de admin. El cuerpo se guarda en Markdown. La imagen destacada se
// sube como archivo y se guarda redimensionada a 800px en WebP.

return function (App $app): void {
    // GET /api/blog?country=PA — posts publicados de una sucursal (recientes primero).
    $app->get('/api/blog', function (ServerRequestInterface $req, ResponseInterface $res) {
        $country = blog_country($req->getQueryParams()['country'] ?? null);

        $sql = 'SELECT * FROM blog_posts WHERE draft = 0';
        $bind = [];
        if ($country !== null) {
            $sql .= ' AND country = ?';
            $bind[] = $country;
        }
        $sql .= ' ORDER BY pub_date DESC, id DESC';

        $stmt = Connection::get()->prepare($sql);
        $stmt->execute($bind);

        $res->getBody()->write(json_encode(array_map('blog_row', $stmt->fetchAll())) ?: '[]');
        return $res->withHeader('Content-Type', 'application/json');
    });

    // GET /api/blog/{country}/{slug} — un post publicado.
    $app->get('/api/blog/{country}/{slug}', function (ServerRequestInterface $req, ResponseInterface $res, array $args) {
        $country = blog_country($args['country'] ?? null);
        $slug = is_string($args['slug'] ?? null) ? $args['slug'] : '';
        if ($country === null || $slug === '') {
            return blog_json($res, ['error' => 'not_found'], 404);
        }
        $stmt = Connection::get()->prepare(
            'SELECT * FROM blog_posts WHERE country = ? AND slug = ? AND draft = 0'
        );
        $stmt->execute([$country, $slug]);
        $row = $stmt->fetch();
        if (!$row) {
            return blog_json($res, ['error' => 'not_found'], 404);
        }
        return blog_json($res, blog_row($row), 200);
    });

    // GET /api/admin/blog — todos los posts (incluye borradores).
    $app->get('/api/admin/blog', function (ServerRequestInterface $req, ResponseInterface $res) {
        $rows = Connection::get()
            ->query('SELECT * FROM blog_posts ORDER BY country ASC, pub_date DESC, id DESC')
            ->fetchAll();
        $res->getBody()->write(json_encode(array_map('blog_row', $rows)) ?: '[]');
        return $res->withHeader('Content-Type', 'application/json');
    })->add(new AdminAuth());

    // POST /api/admin/blog — crear post (multipart: campos + heroImage opcional).
    $app->post('/api/admin/blog', function (ServerRequestInterface $req, ResponseInterface $res) {
        $body = (array) $req->getParsedBody();
        $files = $req->getUploadedFiles();
        if ((int) $req->getHeaderLine('Content-Length') > 0 && $body === [] && $files === []) {
            return blog_json($res, ['error' => 'upload_exceeds_server_limit'], 413);
        }

        $err = null;
        $fields = blog_validate($body, $err);
        if ($err !== null) {
            return blog_json($res, ['error' => $err], 400);
        }

        $hero = null;
        $heroName = blog_store_hero($files['heroImage'] ?? null, $err);
        if ($err !== null) {
            return blog_json($res, ['error' => $err], 400);
        }
        if ($heroName !== null) {
            $hero = '/uploads/blog/' . $heroName;
        }

        $pdo = Connection::get();
        $base = blog_slugify(is_string($body['slug'] ?? null) && trim((string) $body['slug']) !== ''
            ? (string) $body['slug']
            : $fields['title']);
        $slug = blog_unique_slug($pdo, $fields['country'], $base, 0);

        $stmt = $pdo->prepare(
            'INSERT INTO blog_posts
                (country, slug, title, description, body, tags, hero_image, author, pub_date, updated_date, draft)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $fields['country'], $slug, $fields['title'], $fields['description'], $fields['body'],
            $fields['tags'], $hero, $fields['author'],
            $fields['pubDate'], $fields['updatedDate'], $fields['draft'],
        ]);

        return blog_json($res, ['ok' => true, 'id' => (int) $pdo->lastInsertId(), 'slug' => $slug], 201);
    })->add(new AdminAuth());

    // POST /api/admin/blog/{id} — actualizar post (multipart).
    $app->post('/api/admin/blog/{id}', function (ServerRequestInterface $req, ResponseInterface $res, array $args) {
        $id = (int) ($args['id'] ?? 0);
        if ($id <= 0) {
            return blog_json($res, ['error' => 'invalid_id'], 400);
        }
        $pdo = Connection::get();
        $cur = $pdo->prepare('SELECT id, hero_image FROM blog_posts WHERE id = ?');
        $cur->execute([$id]);
        $row = $cur->fetch();
        if ($row === false) {
            return blog_json($res, ['error' => 'not_found'], 404);
        }

        $body = (array) $req->getParsedBody();
        $files = $req->getUploadedFiles();
        if ((int) $req->getHeaderLine('Content-Length') > 0 && $body === [] && $files === []) {
            return blog_json($res, ['error' => 'upload_exceeds_server_limit'], 413);
        }

        $err = null;
        $fields = blog_validate($body, $err);
        if ($err !== null) {
            return blog_json($res, ['error' => $err], 400);
        }

        // Imagen destacada: si suben una nueva, reemplaza (y borra la subida vieja);
        // si marcan removeHero, la quita; si no, conserva la actual.
        $heroUrl = $row['hero_image'];
        $newHero = blog_store_hero($files['heroImage'] ?? null, $err);
        if ($err !== null) {
            return blog_json($res, ['error' => $err], 400);
        }
        if ($newHero !== null) {
            blog_delete_hero($heroUrl);
            $heroUrl = '/uploads/blog/' . $newHero;
        } elseif (in_array($body['removeHero'] ?? null, ['1', 'true', true, 1], true)) {
            blog_delete_hero($heroUrl);
            $heroUrl = null;
        }

        $base = blog_slugify(is_string($body['slug'] ?? null) && trim((string) $body['slug']) !== ''
            ? (string) $body['slug']
            : $fields['title']);
        $slug = blog_unique_slug($pdo, $fields['country'], $base, $id);

        $pdo->prepare(
            'UPDATE blog_posts SET
                country = ?, slug = ?, title = ?, description = ?, body = ?, tags = ?,
                hero_image = ?, author = ?, pub_date = ?, updated_date = ?, draft = ?
             WHERE id = ?'
        )->execute([
            $fields['country'], $slug, $fields['title'], $fields['description'], $fields['body'],
            $fields['tags'], $heroUrl, $fields['author'],
            $fields['pubDate'], $fields['updatedDate'], $fields['draft'], $id,
        ]);

        return blog_json($res, ['ok' => true, 'id' => $id, 'slug' => $slug], 200);
    })->add(new AdminAuth());

    // DELETE /api/admin/blog/{id}
    $app->delete('/api/admin/blog/{id}', function (ServerRequestInterface $req, ResponseInterface $res, array $args) {
        $id = (int) ($args['id'] ?? 0);
        if ($id <= 0) {
            return blog_json($res, ['error' => 'invalid_id'], 400);
        }
        $pdo = Connection::get();
        $cur = $pdo->prepare('SELECT hero_image FROM blog_posts WHERE id = ?');
        $cur->execute([$id]);
        $row = $cur->fetch();
        if ($row === false) {
            return blog_json($res, ['error' => 'not_found'], 404);
        }
        blog_delete_hero($row['hero_image']);
        $pdo->prepare('DELETE FROM blog_posts WHERE id = ?')->execute([$id]);
        return blog_json($res, ['ok' => true], 200);
    })->add(new AdminAuth());
};

function blog_json(ResponseInterface $res, array $data, int $status): ResponseInterface
{
    $res->getBody()->write(json_encode($data) ?: '{}');
    return $res->withHeader('Content-Type', 'application/json')->withStatus($status);
}

/** Normaliza y valida un country ISO-2 soportado; null si no aplica/invalido. */
function blog_country(mixed $v): ?string
{
    if (!is_string($v)) {
        return null;
    }
    $cc = strtoupper(trim($v));
    return in_array($cc, ['PA', 'US', 'ES', 'VE'], true) ? $cc : null;
}

/** Convierte una fila de la BD al shape JSON (camelCase) que consume el front. */
function blog_row(array $r): array
{
    $tags = [];
    if (is_string($r['tags'] ?? null) && $r['tags'] !== '') {
        $decoded = json_decode($r['tags'], true);
        if (is_array($decoded)) {
            $tags = array_values(array_filter($decoded, 'is_string'));
        }
    }

    return [
        'id'          => (int) $r['id'],
        'country'     => $r['country'],
        'slug'        => $r['slug'],
        'title'       => $r['title'],
        'description' => $r['description'],
        'body'        => $r['body'],
        'tags'        => $tags,
        'heroImage'   => $r['hero_image'],
        'author'      => $r['author'],
        'pubDate'     => $r['pub_date'],
        'updatedDate' => $r['updated_date'],
        'draft'       => (bool) $r['draft'],
        'createdAt'   => $r['created_at'] ?? null,
        'updatedAt'   => $r['updated_at'] ?? null,
    ];
}

/**
 * Valida el payload de create/update y devuelve los campos ya normalizados.
 * Setea $err con un codigo si algo falla. (La imagen destacada se maneja aparte.)
 *
 * @return array{country:string,title:string,description:string,body:string,tags:?string,author:string,pubDate:string,updatedDate:?string,draft:int}
 */
function blog_validate(array $body, ?string &$err): array
{
    $err = null;
    $out = [
        'country' => '', 'title' => '', 'description' => '', 'body' => '',
        'tags' => null, 'author' => 'Kenny Diaz',
        'pubDate' => '', 'updatedDate' => null, 'draft' => 0,
    ];

    $country = blog_country($body['country'] ?? null);
    if ($country === null) {
        $err = 'invalid_country';
        return $out;
    }
    $out['country'] = $country;

    foreach (['title', 'description', 'body'] as $req) {
        $val = is_string($body[$req] ?? null) ? trim((string) $body[$req]) : '';
        if ($val === '') {
            $err = "missing_{$req}";
            return $out;
        }
        $out[$req] = $val;
    }

    $pub = blog_date($body['pubDate'] ?? null);
    if ($pub === null) {
        $err = 'invalid_pub_date';
        return $out;
    }
    $out['pubDate'] = $pub;
    $out['updatedDate'] = blog_date($body['updatedDate'] ?? null); // null si vacio/invalido

    $author = is_string($body['author'] ?? null) ? trim((string) $body['author']) : '';
    if ($author !== '') {
        $out['author'] = $author;
    }

    $out['tags'] = blog_tags($body['tags'] ?? null);
    $out['draft'] = in_array($body['draft'] ?? false, [true, 1, '1', 'true'], true) ? 1 : 0;

    return $out;
}

/** Acepta una fecha 'YYYY-MM-DD' valida y la devuelve normalizada, o null. */
function blog_date(mixed $v): ?string
{
    if (!is_string($v)) {
        return null;
    }
    $v = trim($v);
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $v) !== 1) {
        return null;
    }
    [$y, $m, $d] = array_map('intval', explode('-', $v));
    return checkdate($m, $d, $y) ? $v : null;
}

/** Normaliza tags (array u CSV) a un JSON string para la columna, o null si vacio. */
function blog_tags(mixed $v): ?string
{
    $list = [];
    if (is_array($v)) {
        $list = $v;
    } elseif (is_string($v) && trim($v) !== '') {
        $list = explode(',', $v);
    }
    $clean = [];
    $seen = [];
    foreach ($list as $t) {
        if (!is_string($t)) {
            continue;
        }
        $t = trim($t);
        if ($t !== '' && !isset($seen[$t])) {
            $seen[$t] = true;
            $clean[] = $t;
        }
    }
    return $clean === [] ? null : (json_encode($clean, JSON_UNESCAPED_UNICODE) ?: null);
}

/** Slug URL-safe a partir de un texto (translitera acentos comunes). */
function blog_slugify(string $s): string
{
    $from = ['á','é','í','ó','ú','ü','ñ','Á','É','Í','Ó','Ú','Ü','Ñ','ç','Ç','ä','ö'];
    $to   = ['a','e','i','o','u','u','n','a','e','i','o','u','u','n','c','c','a','o'];
    $s = str_replace($from, $to, trim($s));
    $s = function_exists('mb_strtolower') ? mb_strtolower($s, 'UTF-8') : strtolower($s);
    $s = preg_replace('/[^a-z0-9]+/', '-', $s) ?? '';
    $s = trim($s, '-');
    return $s === '' ? 'post' : substr($s, 0, 160);
}

/** Garantiza un slug unico dentro de la sucursal (sufija -2, -3, ... si choca). */
function blog_unique_slug(PDO $pdo, string $country, string $base, int $excludeId): string
{
    $q = $pdo->prepare('SELECT id FROM blog_posts WHERE country = ? AND slug = ? AND id <> ?');
    $slug = $base;
    $n = 1;
    while (true) {
        $q->execute([$country, $slug, $excludeId]);
        if ($q->fetch() === false) {
            return $slug;
        }
        $n++;
        $slug = $base . '-' . $n;
    }
}

/**
 * Guarda la imagen destacada subida: la redimensiona a un lado maximo de 800px
 * (solo si lo excede), la convierte a WebP y la deja en public/uploads/blog/.
 * Devuelve el filename .webp, o null si no se subio archivo. Setea $err si falla.
 */
function blog_store_hero(?UploadedFileInterface $file, ?string &$err = null): ?string
{
    $err = null;
    if (!$file instanceof UploadedFileInterface || $file->getError() === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($file->getError() !== UPLOAD_ERR_OK) {
        $err = 'hero_upload_error_' . $file->getError();
        return null;
    }
    $size = $file->getSize();
    if ($size === null || $size > 10 * 1024 * 1024) {
        $err = 'hero_too_large_max_10mb';
        return null;
    }
    $mime = $file->getClientMediaType() ?? '';
    if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp', 'image/gif'], true)) {
        $err = 'hero_invalid_mime_type';
        return null;
    }
    if (!extension_loaded('gd') || !function_exists('imagewebp')) {
        $err = 'webp_not_supported';
        return null;
    }

    try {
        $stream = $file->getStream();
        $stream->rewind();
        $data = $stream->getContents();
    } catch (\Throwable $e) {
        $err = 'hero_read_failed';
        return null;
    }

    $src = @imagecreatefromstring($data);
    if (!$src instanceof \GdImage) {
        $err = 'hero_decode_failed';
        return null;
    }

    $w = imagesx($src);
    $h = imagesy($src);
    $max = 800;
    $scale = ($w > $max || $h > $max) ? $max / max($w, $h) : 1.0;
    $nw = max(1, (int) round($w * $scale));
    $nh = max(1, (int) round($h * $scale));

    $dst = imagecreatetruecolor($nw, $nh);
    imagealphablending($dst, false);
    imagesavealpha($dst, true);
    $transparent = imagecolorallocatealpha($dst, 0, 0, 0, 127);
    imagefilledrectangle($dst, 0, 0, $nw, $nh, $transparent);
    imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);

    $dir = __DIR__ . '/../../public/uploads/blog';
    if (!is_dir($dir)) {
        mkdir($dir, 0o755, true);
    }
    $filename = bin2hex(random_bytes(16)) . '.webp';
    $ok = imagewebp($dst, $dir . '/' . $filename, 82);

    imagedestroy($src);
    imagedestroy($dst);

    if ($ok !== true) {
        $err = 'hero_encode_failed';
        return null;
    }
    return $filename;
}

/** Borra del disco una imagen destacada subida (solo si vive en uploads/blog/). */
function blog_delete_hero(mixed $heroUrl): void
{
    if (is_string($heroUrl) && str_starts_with($heroUrl, '/uploads/blog/')) {
        $path = __DIR__ . '/../../public/uploads/blog/' . basename($heroUrl);
        if (is_file($path)) {
            @unlink($path);
        }
    }
}
