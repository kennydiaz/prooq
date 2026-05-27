<?php

declare(strict_types=1);

namespace Prooq\Api\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class Cors implements MiddlewareInterface
{
    // Las 4 sucursales viven en paths bajo prooq.com (/pty, /usa, /esp, /ven),
    // por eso un solo origin de produccion. localhost para dev de cada app.
    private const ALLOWED_ORIGINS = [
        'https://prooq.com',
        'https://www.prooq.com',
        'http://localhost:4321',
        'http://localhost:4322',
        'http://localhost:4323',
        'http://localhost:4324',
        'http://localhost:4325',
    ];

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $origin = $request->getHeaderLine('Origin');
        $allowedOrigin = $this->isAllowed($origin) ? $origin : '';

        // Preflight: responder el OPTIONS aqui mismo. Si pasara al router, Slim
        // devolveria 404/405 y el navegador abortaria la peticion real.
        if (strtoupper($request->getMethod()) === 'OPTIONS') {
            $response = new \Slim\Psr7\Response(204);
            return $allowedOrigin === '' ? $response : $this->withCorsHeaders($response, $allowedOrigin);
        }

        $response = $handler->handle($request);

        if ($allowedOrigin === '') {
            return $response;
        }

        return $this->withCorsHeaders($response, $allowedOrigin);
    }

    private function isAllowed(string $origin): bool
    {
        if ($origin === '') {
            return false;
        }
        if (in_array($origin, self::ALLOWED_ORIGINS, true)) {
            return true;
        }
        // Dev: cualquier puerto de localhost / 127.0.0.1 sobre http.
        return (bool) preg_match('#^http://(localhost|127\.0\.0\.1)(:\d+)?$#', $origin);
    }

    private function withCorsHeaders(ResponseInterface $response, string $origin): ResponseInterface
    {
        return $response
            ->withHeader('Access-Control-Allow-Origin', $origin)
            ->withHeader('Access-Control-Allow-Credentials', 'true')
            ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
            ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With')
            ->withHeader('Access-Control-Max-Age', '600')
            ->withHeader('Vary', 'Origin');
    }
}
