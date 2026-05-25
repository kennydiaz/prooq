<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;
use Prooq\Api\Middleware\Cors;
use Prooq\Api\Middleware\RateLimit;
use Slim\Factory\AppFactory;

Dotenv::createImmutable(__DIR__ . '/..')->safeLoad();

$app = AppFactory::create();

$app->addBodyParsingMiddleware();
$app->addRoutingMiddleware();
$app->addErrorMiddleware(
    displayErrorDetails: ($_ENV['APP_DEBUG'] ?? 'false') === 'true',
    logErrors: true,
    logErrorDetails: true,
);

// Orden de middleware: el último .add() corre primero por request.
$app->add(new RateLimit());
$app->add(new Cors());

(require __DIR__ . '/../src/Routes/clients.php')($app);
(require __DIR__ . '/../src/Routes/downloads.php')($app);
(require __DIR__ . '/../src/Routes/ebop.php')($app);
(require __DIR__ . '/../src/Routes/chat.php')($app);

$app->run();
