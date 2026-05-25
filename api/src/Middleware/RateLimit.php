<?php

declare(strict_types=1);

namespace Prooq\Api\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;

// Limita por IP usando APCu. Si APCu no está disponible (no debería en Hostinger),
// el middleware se vuelve no-op — preferible a romper requests.
final class RateLimit implements MiddlewareInterface
{
    private const REQUESTS_PER_MINUTE = 60;
    private const WINDOW_SECONDS = 60;

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (!function_exists('apcu_fetch') || !function_exists('apcu_store')) {
            return $handler->handle($request);
        }

        $ip = $request->getServerParams()['REMOTE_ADDR'] ?? 'unknown';
        $key = 'prooq:ratelimit:' . $ip;

        $count = (int) apcu_fetch($key);

        if ($count >= self::REQUESTS_PER_MINUTE) {
            $response = new Response();
            $response->getBody()->write(json_encode([
                'error'       => 'rate_limited',
                'retry_after' => self::WINDOW_SECONDS,
            ]) ?: '{}');
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withHeader('Retry-After', (string) self::WINDOW_SECONDS)
                ->withStatus(429);
        }

        apcu_store($key, $count + 1, self::WINDOW_SECONDS);

        return $handler->handle($request);
    }
}
