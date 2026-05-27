<?php

declare(strict_types=1);

use Prooq\Api\Db\Connection;
use Prooq\Api\Middleware\AdminAuth;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;

return function (App $app): void {
    // Modulo Dashboard / analytics. Todo bajo sesion admin.
    $app->group('/api/admin/stats', function (RouteCollectorProxy $group): void {
        // GET /api/admin/stats/visits/summary — KPIs principales.
        $group->get('/visits/summary', function (ServerRequestInterface $req, ResponseInterface $res) {
            $pdo = Connection::get();
            $scalar = static fn (string $sql): int => (int) $pdo->query($sql)->fetchColumn();

            $total      = $scalar('SELECT COUNT(*) FROM page_visits');
            $uniqueIps  = $scalar('SELECT COUNT(DISTINCT ip) FROM page_visits WHERE ip IS NOT NULL');
            $today      = $scalar('SELECT COUNT(*) FROM page_visits WHERE DATE(created_at) = CURDATE()');
            $last7      = $scalar('SELECT COUNT(*) FROM page_visits WHERE created_at >= (CURDATE() - INTERVAL 6 DAY)');
            $last30     = $scalar('SELECT COUNT(*) FROM page_visits WHERE created_at >= (CURDATE() - INTERVAL 29 DAY)');
            $countries  = $scalar('SELECT COUNT(DISTINCT country) FROM page_visits WHERE country IS NOT NULL');

            $topRow = $pdo->query(
                'SELECT country, COUNT(*) c FROM page_visits
                 WHERE country IS NOT NULL GROUP BY country ORDER BY c DESC LIMIT 1'
            )->fetch();

            $payload = [
                'total'      => $total,
                'uniqueIps'  => $uniqueIps,
                'today'      => $today,
                'last7'      => $last7,
                'last30'     => $last30,
                'countries'  => $countries,
                'topCountry' => $topRow ? ['country' => $topRow['country'], 'count' => (int) $topRow['c']] : null,
            ];
            $res->getBody()->write(json_encode($payload) ?: '{}');
            return $res->withHeader('Content-Type', 'application/json');
        });

        // GET /api/admin/stats/visits/timeseries?days=14 — visitas por dia (con huecos rellenos).
        $group->get('/visits/timeseries', function (ServerRequestInterface $req, ResponseInterface $res) {
            $days = (int) ($req->getQueryParams()['days'] ?? 14);
            $days = max(1, min($days, 90));

            $stmt = Connection::get()->prepare(
                'SELECT DATE(created_at) d, COUNT(*) c FROM page_visits
                 WHERE created_at >= (CURDATE() - INTERVAL ? DAY)
                 GROUP BY DATE(created_at)'
            );
            $stmt->execute([$days - 1]);
            $byDay = [];
            foreach ($stmt->fetchAll() as $row) {
                $byDay[(string) $row['d']] = (int) $row['c'];
            }

            $series = [];
            for ($i = $days - 1; $i >= 0; $i--) {
                $date = date('Y-m-d', strtotime("-{$i} day"));
                $series[] = ['date' => $date, 'count' => $byDay[$date] ?? 0];
            }
            $res->getBody()->write(json_encode($series) ?: '[]');
            return $res->withHeader('Content-Type', 'application/json');
        });

        // GET /api/admin/stats/visits/by-country?limit=8 — visitas agrupadas por pais.
        $group->get('/visits/by-country', function (ServerRequestInterface $req, ResponseInterface $res) {
            $limit = (int) ($req->getQueryParams()['limit'] ?? 8);
            $limit = max(1, min($limit, 50));

            $stmt = Connection::get()->prepare(
                'SELECT COALESCE(country, "??") country, COUNT(*) c FROM page_visits
                 GROUP BY country ORDER BY c DESC LIMIT ?'
            );
            $stmt->bindValue(1, $limit, PDO::PARAM_INT);
            $stmt->execute();
            $rows = array_map(
                static fn (array $r): array => ['country' => $r['country'], 'count' => (int) $r['c']],
                $stmt->fetchAll()
            );
            $res->getBody()->write(json_encode($rows) ?: '[]');
            return $res->withHeader('Content-Type', 'application/json');
        });

        // GET /api/admin/stats/visits/recent?limit=50 — ultimas visitas (IP + pais).
        $group->get('/visits/recent', function (ServerRequestInterface $req, ResponseInterface $res) {
            $limit = (int) ($req->getQueryParams()['limit'] ?? 50);
            $limit = max(1, min($limit, 200));

            $stmt = Connection::get()->prepare(
                'SELECT id, path, site, country, ip, created_at AS createdAt
                 FROM page_visits ORDER BY created_at DESC LIMIT ?'
            );
            $stmt->bindValue(1, $limit, PDO::PARAM_INT);
            $stmt->execute();
            $rows = array_map(
                static fn (array $r): array => [
                    'id'        => (int) $r['id'],
                    'path'      => $r['path'],
                    'site'      => $r['site'],
                    'country'   => $r['country'],
                    'ip'        => $r['ip'],
                    'createdAt' => $r['createdAt'],
                ],
                $stmt->fetchAll()
            );
            $res->getBody()->write(json_encode($rows) ?: '[]');
            return $res->withHeader('Content-Type', 'application/json');
        });
    })->add(new AdminAuth());
};
