<?php

declare(strict_types=1);

namespace Prooq\Api\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class Cors implements MiddlewareInterface
{
    private const ALLOWED_ORIGINS = [
        'https://prooq.com',
        'https://pty.prooq.com',
        'https://usa.prooq.com',
        'https://esp.prooq.com',
        'https://ven.prooq.com',
        'http://localhost:4321',
        'http://localhost:4322',
        'http://localhost:4323',
        'http://localhost:4324',
        'http://localhost:4325',
    ];

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $origin = $request->getHeaderLine('Origin');
        $allowedOrigin = in_array($origin, self::ALLOWED_ORIGINS, true) ? $origin : '';

        $response = $handler->handle($request);

        if ($allowedOrigin === '') {
            return $response;
        }

        return $response
            ->withHeader('Access-Control-Allow-Origin', $allowedOrigin)
            ->withHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS')
            ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization')
            ->withHeader('Access-Control-Max-Age', '600')
            ->withHeader('Vary', 'Origin');
    }
}
