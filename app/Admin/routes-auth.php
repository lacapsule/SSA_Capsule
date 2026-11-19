<?php
declare(strict_types=1);

/*
 * Routes de connexion et de sécurité du back-office. Dans app/routes.php, avant « return $router; » :
 *   require __DIR__ . '/Admin/routes-auth.php';
 * Chaque écran du back-office commence par AdminAuth::require() (redirige vers la connexion si besoin).
 */

use App\Admin\AdminController as Auth;

/** @var App\Core\Router $router */
$base = rtrim((string) config('admin.base', '/admin'), '/');

$router->get("$base/connexion/", [Auth::class, 'login']);
$router->post("$base/connexion/", [Auth::class, 'doLogin']);
$router->get("$base/verification/", [Auth::class, 'verify']);
$router->post("$base/verification/", [Auth::class, 'doVerify']);
$router->get("$base/double-authentification/", [Auth::class, 'enroll']);
$router->post("$base/double-authentification/", [Auth::class, 'doEnroll']);
$router->get("$base/codes-de-secours/", [Auth::class, 'recoveryCodes']);
$router->get("$base/mot-de-passe-oublie/", [Auth::class, 'forgot']);
$router->post("$base/mot-de-passe-oublie/", [Auth::class, 'sendForgot']);
$router->get("$base/nouveau-mot-de-passe/", [Auth::class, 'reset']);
$router->post("$base/nouveau-mot-de-passe/", [Auth::class, 'saveReset']);
$router->get("$base/securite/", [Auth::class, 'securityPage']);
$router->post("$base/securite/", [Auth::class, 'security']);
$router->post("$base/deconnexion/", [Auth::class, 'logout']);
