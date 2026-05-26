<?php

declare(strict_types=1);

namespace Prooq\Api\Middleware;

use Prooq\Api\Auth\Session;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;

/**
 * Middleware que requiere sesión activa de admin. Si no hay sesión, devuelve
 * 401 con JSON. Aplicar solo a rutas /api/admin/*.
 */
final class AdminAuth implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (!Session::isAuthenticated()) {
            $res = new Response();
            $res->getBody()->write(json_encode(['error' => 'unauthorized']) ?: '{}');
            return $res
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(401);
        }
        return $handler->handle($request);
    }
}
