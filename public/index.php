<?php

declare(strict_types=1);

// Autoload
require dirname(__DIR__) . '/src/Autoload.php';

use Capsule\Http\Message\Request;
use Capsule\Http\Emitter\SapiEmitter;
use Capsule\Kernel\KernelHttp;
use Capsule\Http\Middleware\{
    ErrorBoundary,
    DebugHeaders,
    SecurityHeaders,
    SessionMiddleware,
    LangMiddleware,
    AuthRequiredMiddleware,
    HealthCheckMiddleware
};

// Récupérer container + router
[$container, $router] = require dirname(__DIR__) . '/bootstrap/app.php';

// Construire la Request
$request = Request::fromGlobals();

// Middlewares (ordre CRITIQUE : top-down)
$middlewares = [
    $container->get(ErrorBoundary::class),       // 1. Capture toutes les erreurs
    $container->get(DebugHeaders::class),        // 2. Headers de debug
    $container->get(SecurityHeaders::class),     // 3. Headers de sécurité
    $container->get(HealthCheckMiddleware::class), // 4. Vérifie l'état de la base
    new SessionMiddleware(),                     // 4. ✅ Démarre la session
    $container->get(LangMiddleware::class),      // 5. Détection langue (lit session)
    $container->get(AuthRequiredMiddleware::class), // 6. Protection routes (lit session)
];

// Kernel = orchestration pure (pas d'I/O)
$kernel = new KernelHttp($middlewares, $router);

// Exécution
$response = $kernel->handle($request);

// Émission
$emitter = new SapiEmitter();
$emitter->emit($response);
