<?php

declare(strict_types=1);

// Autoload
require dirname(__DIR__) . '/src/Autoload.php';

use Capsule\Http\Message\Request;
use Capsule\Http\Emitter\SapiEmitter;
use Capsule\Kernel\KernelHttp;
use Capsule\Auth\PhpSessionReader;
use Capsule\Http\Middleware\{
    ErrorBoundary,
    SecurityHeaders
};

// Sécurité session/env (pas d’I/O applicatif)
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Strict');
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Récupérer container + router
[$container, $router] = require dirname(__DIR__) . '/bootstrap/app.php';

// Construire la Request
$request = Request::fromGlobals();
$session = new PhpSessionReader();

// Middlewares (via DI quand dispo)
$middlewares = [
    $container->get(ErrorBoundary::class),
    $container->get(\Capsule\Http\Middleware\DebugHeaders::class),
    $container->get(SecurityHeaders::class),
    $container->get(\Capsule\Http\Middleware\AuthRequiredMiddleware::class),
];

// Kernel = orchestration pure
$kernel = new KernelHttp($middlewares, $router);

// Exécution
$response = $kernel->handle($request);

// Émission
$emitter = new SapiEmitter();
$emitter->emit($response);
