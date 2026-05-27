<?php

declare(strict_types=1);

use Prooq\Api\Db\Connection;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\App;

return function (App $app): void {
    // POST /api/track/visit — registra una visita. Publico (lo llaman las
    // paginas de cada sucursal). La IP y el user-agent se toman server-side;
    // el cliente solo envia { path, site?, referrer? }.
    $app->post('/api/track/visit', function (ServerRequestInterface $req, ResponseInterface $res) {
        $body = (array) $req->getParsedBody();

        $path = is_string($body['path'] ?? null) ? trim((string) $body['path']) : '/';
        $path = $path === '' ? '/' : substr($path, 0, 255);

        $site = null;
        if (isset($body['site']) && is_string($body['site']) && preg_match('/^[a-z]{3,8}$/', $body['site']) === 1) {
            $site = $body['site'];
        }

        $referrer = is_string($body['referrer'] ?? null) ? substr((string) $body['referrer'], 0, 500) : null;
        $ua = substr($req->getHeaderLine('User-Agent'), 0, 500);
        $ua = $ua === '' ? null : $ua;

        $ip = track_client_ip($req);
        $country = track_resolve_country($ip);

        Connection::get()->prepare(
            'INSERT INTO page_visits (path, site, country, ip, user_agent, referrer)
             VALUES (?, ?, ?, ?, ?, ?)'
        )->execute([$path, $site, $country, $ip, $ua, $referrer ?: null]);

        $res->getBody()->write(json_encode(['ok' => true]) ?: '{}');
        return $res->withHeader('Content-Type', 'application/json')->withStatus(202);
    });
};

/** IP del cliente: prioriza X-Forwarded-For (proxy/Hostinger), cae a REMOTE_ADDR. */
function track_client_ip(ServerRequestInterface $req): ?string
{
    $fwd = $req->getHeaderLine('X-Forwarded-For');
    if ($fwd !== '') {
        $first = trim(explode(',', $fwd)[0]);
        if ($first !== '') {
            return substr($first, 0, 45);
        }
    }
    $ip = $req->getServerParams()['REMOTE_ADDR'] ?? null;
    return is_string($ip) && $ip !== '' ? substr($ip, 0, 45) : null;
}

/**
 * Pais (ISO-2) de una IP. IPs privadas/loopback -> null. Cachea por IP
 * reutilizando un country ya resuelto en page_visits, y si no, hace un lookup
 * best-effort a ip-api.com (sin API key, timeout corto). Si falla -> null.
 */
function track_resolve_country(?string $ip): ?string
{
    if ($ip === null || $ip === '') {
        return null;
    }
    // Rechaza privadas/reservadas (incluye 127.0.0.1, ::1, 192.168.x, etc.).
    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
        return null;
    }

    try {
        $stmt = Connection::get()->prepare(
            'SELECT country FROM page_visits WHERE ip = ? AND country IS NOT NULL LIMIT 1'
        );
        $stmt->execute([$ip]);
        $cached = $stmt->fetchColumn();
        if (is_string($cached) && $cached !== '') {
            return $cached;
        }
    } catch (\Throwable $e) {
        // Sin cache; seguimos al lookup externo.
    }

    $ch = curl_init('http://ip-api.com/json/' . urlencode($ip) . '?fields=status,countryCode');
    if ($ch === false) {
        return null;
    }
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 2,
        CURLOPT_CONNECTTIMEOUT => 2,
    ]);
    $out = curl_exec($ch);
    curl_close($ch);

    if (is_string($out)) {
        $j = json_decode($out, true);
        if (is_array($j)
            && ($j['status'] ?? '') === 'success'
            && is_string($j['countryCode'] ?? null)
            && preg_match('/^[A-Z]{2}$/', $j['countryCode']) === 1
        ) {
            return $j['countryCode'];
        }
    }
    return null;
}
