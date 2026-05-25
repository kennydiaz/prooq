<?php

declare(strict_types=1);

use Prooq\Api\Db\Connection;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\App;

return function (App $app): void {
    $app->post('/api/ebop', function (ServerRequestInterface $req, ResponseInterface $res) {
        $body = (array) $req->getParsedBody();

        foreach (['company_name', 'contact_name', 'email'] as $field) {
            if (empty($body[$field]) || !is_string($body[$field])) {
                $res->getBody()->write(json_encode(['error' => "missing_$field"]) ?: '{}');
                return $res->withHeader('Content-Type', 'application/json')->withStatus(400);
            }
        }

        if (filter_var($body['email'], FILTER_VALIDATE_EMAIL) === false) {
            $res->getBody()->write(json_encode(['error' => 'invalid_email']) ?: '{}');
            return $res->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        $pdo = Connection::get();
        $stmt = $pdo->prepare(
            'INSERT INTO ebop_requests (company_name, contact_name, email, phone, message, ip_address)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $body['company_name'],
            $body['contact_name'],
            $body['email'],
            $body['phone'] ?? null,
            $body['message'] ?? null,
            $req->getServerParams()['REMOTE_ADDR'] ?? null,
        ]);

        $payload = json_encode(['ok' => true, 'id' => (int) $pdo->lastInsertId()]);
        $res->getBody()->write($payload ?: '{}');
        return $res->withHeader('Content-Type', 'application/json')->withStatus(201);
    });
};
