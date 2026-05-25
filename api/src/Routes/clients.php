<?php

declare(strict_types=1);

use Prooq\Api\Db\Connection;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\App;

return function (App $app): void {
    $app->get('/api/clients', function (ServerRequestInterface $req, ResponseInterface $res) {
        $country = $req->getQueryParams()['country'] ?? null;

        $sql = 'SELECT id, name, logo_url AS logoUrl, website, country, industry,
                       display_order AS displayOrder, is_active AS isActive
                FROM clients
                WHERE is_active = 1';
        $bindings = [];

        if (is_string($country) && preg_match('/^[A-Z]{2}$/', $country) === 1) {
            $sql .= ' AND country = ?';
            $bindings[] = $country;
        }

        $sql .= ' ORDER BY display_order ASC, name ASC';

        $stmt = Connection::get()->prepare($sql);
        $stmt->execute($bindings);
        $rows = $stmt->fetchAll();

        foreach ($rows as &$row) {
            $row['isActive'] = (bool) $row['isActive'];
            $row['displayOrder'] = (int) $row['displayOrder'];
        }

        $res->getBody()->write(json_encode($rows) ?: '[]');
        return $res->withHeader('Content-Type', 'application/json');
    });
};
