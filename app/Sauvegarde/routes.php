<?php
declare(strict_types=1);

/*
 * Routes du module. Dans app/routes.php, avant « return $router; » :
 *   (require __DIR__ . '/Sauvegarde/routes.php')($router);
 * Préfixe POST : admin/ figure déjà dans la ligne THE_REQUEST de public/.htaccess.
 */

use App\Admin\Admin;
use App\Core\Router;
use App\Sauvegarde\SauvegardeController as S;

return static function (Router $router): void {
    $router->get(Admin::url('sauvegardes/'), [S::class, 'index']);
    $router->post(Admin::url('sauvegardes/lancer/'), [S::class, 'lancer']);
    $router->post(Admin::url('sauvegardes/verifier/'), [S::class, 'verifier']);
    $router->get(Admin::url('sauvegardes/restaurer/'), [S::class, 'confirmer']);
    $router->post(Admin::url('sauvegardes/restaurer/'), [S::class, 'restaurer']);
};
