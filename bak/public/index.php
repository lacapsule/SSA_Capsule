<?php

declare(strict_types=1);

// --- Autoload (temporaire). Idéalement: require dirname(__DIR__).'/vendor/autoload.php';
require dirname(__DIR__) . '/lib/Autoload.php';

use CapsuleLib\Http\SecureHeaders;

// Sécurité session
ini_set('session.cookie_httponly', '1');
//ini_set('session.cookie_secure', '1'); // active en HTTPS
ini_set('session.cookie_samesite', 'Strict');

// Affichage erreurs (dev)
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

// Session
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// En-têtes sécurisés (si ta classe existe)
if (class_exists(SecureHeaders::class)) {
    SecureHeaders::send();
}

// Bootstrap (ATTENTION au chemin !)
$bootstrapPath = dirname(__DIR__) . '/bootstrap/app.php';
if (!is_file($bootstrapPath)) {
    http_response_code(500);
    echo "Bootstrap introuvable: {$bootstrapPath}";
    exit;
}

/** @var \CapsuleLib\Routing\Router $router */
$router = require $bootstrapPath;

// Dispatch
$router->dispatch();
