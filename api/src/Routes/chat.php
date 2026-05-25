<?php

declare(strict_types=1);

use Prooq\Api\Db\Connection;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\App;

return function (App $app): void {
    $app->post('/api/chat', function (ServerRequestInterface $req, ResponseInterface $res) {
        $body = (array) $req->getParsedBody();

        $message = $body['message'] ?? null;
        $conversationId = $body['conversationId'] ?? null;
        $sourceSite = $body['sourceSite'] ?? null;

        if (!is_string($message) || !is_string($conversationId) || $message === '' || $conversationId === '') {
            $res->getBody()->write(json_encode(['error' => 'missing_fields']) ?: '{}');
            return $res->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        $webhook = $_ENV['N8N_WEBHOOK_URL'] ?? '';
        if ($webhook === '') {
            $res->getBody()->write(json_encode(['error' => 'webhook_not_configured']) ?: '{}');
            return $res->withHeader('Content-Type', 'application/json')->withStatus(500);
        }

        $logStmt = Connection::get()->prepare(
            'INSERT INTO chat_logs (conversation_id, role, message, source_site) VALUES (?, ?, ?, ?)'
        );
        $logStmt->execute([$conversationId, 'user', $message, $sourceSite]);

        $ch = curl_init($webhook);
        if ($ch === false) {
            $res->getBody()->write(json_encode(['error' => 'curl_init_failed']) ?: '{}');
            return $res->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
        // N8N workflow espera el shape del V1: chatInput, conversationId,
        // timestamp ISO8601, source. Si cambias el workflow para aceptar otros
        // campos, actualiza este payload.
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS     => json_encode([
                'chatInput'      => $message,
                'conversationId' => $conversationId,
                'timestamp'      => gmdate('Y-m-d\TH:i:s\Z'),
                'source'         => 'website-chat',
                'sourceSite'     => $sourceSite,
            ]),
            CURLOPT_TIMEOUT        => 30,
        ]);
        $n8nResponse = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || !is_string($n8nResponse)) {
            $res->getBody()->write(json_encode(['error' => 'n8n_failed', 'status' => $httpCode]) ?: '{}');
            return $res->withHeader('Content-Type', 'application/json')->withStatus(502);
        }

        // N8N puede devolver reply, response, message u output segun el nodo
        // final del workflow. Aceptamos cualquiera (compatible con V1).
        $parsed = json_decode($n8nResponse, true);
        $reply = '';
        if (is_array($parsed)) {
            foreach (['reply', 'response', 'message', 'output'] as $key) {
                if (isset($parsed[$key]) && is_string($parsed[$key])) {
                    $reply = $parsed[$key];
                    break;
                }
            }
        }
        if ($reply === '') {
            // El workflow devolvio 200 pero sin campo conocido — log raw para debug.
            error_log('chat: N8N 200 sin reply conocido. Body: ' . substr((string) $n8nResponse, 0, 500));
        }

        $logStmt->execute([$conversationId, 'assistant', $reply, $sourceSite]);

        $payload = json_encode([
            'reply'          => $reply,
            'conversationId' => $conversationId,
        ]);
        $res->getBody()->write($payload ?: '{}');
        return $res->withHeader('Content-Type', 'application/json');
    });
};
