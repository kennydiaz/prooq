<?php

declare(strict_types=1);

use Prooq\Api\Auth\Session;
use Prooq\Api\Db\Connection;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\App;

return function (App $app): void {
    // POST /api/admin/login — { username, password } → 200 + session cookie | 401
    $app->post('/api/admin/login', function (ServerRequestInterface $req, ResponseInterface $res) {
        $body = (array) $req->getParsedBody();
        $username = $body['username'] ?? null;
        $password = $body['password'] ?? null;

        if (!is_string($username) || !is_string($password) || $username === '' || $password === '') {
            $res->getBody()->write(json_encode(['error' => 'missing_credentials']) ?: '{}');
            return $res->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        $stmt = Connection::get()->prepare(
            'SELECT id, username, password_hash, role FROM admin_users WHERE username = ? LIMIT 1'
        );
        $stmt->execute([$username]);
        $row = $stmt->fetch();

        if (!$row || !password_verify($password, (string) $row['password_hash'])) {
            // Sleep aleatorio 100-300ms para mitigar timing attacks contra usernames validos vs invalidos.
            usleep(random_int(100_000, 300_000));
            $res->getBody()->write(json_encode(['error' => 'invalid_credentials']) ?: '{}');
            return $res->withHeader('Content-Type', 'application/json')->withStatus(401);
        }

        Session::login((int) $row['id'], (string) $row['username'], (string) $row['role']);

        $res->getBody()->write(json_encode([
            'ok' => true,
            'user' => [
                'id' => (int) $row['id'],
                'username' => $row['username'],
                'role' => $row['role'],
            ],
        ]) ?: '{}');
        return $res->withHeader('Content-Type', 'application/json');
    });

    // POST /api/admin/logout — destruye sesión
    $app->post('/api/admin/logout', function (ServerRequestInterface $req, ResponseInterface $res) {
        Session::logout();
        $res->getBody()->write(json_encode(['ok' => true]) ?: '{}');
        return $res->withHeader('Content-Type', 'application/json');
    });

    // GET /api/admin/me — devuelve usuario actual si está autenticado
    $app->get('/api/admin/me', function (ServerRequestInterface $req, ResponseInterface $res) {
        Session::start();
        $userId = Session::currentUserId();
        if ($userId === null) {
            $res->getBody()->write(json_encode(['authenticated' => false]) ?: '{}');
            return $res->withHeader('Content-Type', 'application/json');
        }
        $res->getBody()->write(json_encode([
            'authenticated' => true,
            'user' => [
                'id' => $userId,
                'username' => $_SESSION['admin_username'] ?? null,
                'role' => $_SESSION['admin_role'] ?? null,
            ],
        ]) ?: '{}');
        return $res->withHeader('Content-Type', 'application/json');
    });
};
