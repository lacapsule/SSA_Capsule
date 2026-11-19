<?php
declare(strict_types=1);

/*
 * Routes du minuteur de session. Dans app/routes.php, avant « return $router; » :
 *   (require __DIR__ . '/Session/routes.php')($router);
 * Adresses sans slash final (appelées par le script, jamais redirigées). Préfixe POST « session/ » à ajouter
 * à la ligne THE_REQUEST du .htaccess.
 */

use App\Core\Router;
use App\Session\MinuteurController as M;

return static function (Router $router): void {
    $router->get('/session/etat', [M::class, 'etat']);
    $router->post('/session/prolonger', [M::class, 'prolonger']);
    $router->get('/session/expiree', [M::class, 'expiree']);
};
