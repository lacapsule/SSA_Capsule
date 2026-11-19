<?php
declare(strict_types=1);

/*
 * Écrans des comptes du back-office. Dans app/routes.php, avant « return $router; » (après routes-auth.php) :
 *   (require __DIR__ . '/Admin/routes-comptes.php')($router);
 */

use App\Admin\AccountController as A;
use App\Admin\Admin;
use App\Core\Router;

return static function (Router $router): void {
    $router->get(Admin::url('comptes/'), [A::class, 'index']);
    $router->post(Admin::url('comptes/'), [A::class, 'store']);
    $router->get(Admin::url('comptes/nouveau/'), [A::class, 'create']);
    $router->get(Admin::url('comptes/{id}/'), [A::class, 'edit']);
    $router->post(Admin::url('comptes/{id}/'), [A::class, 'update']);
    $router->post(Admin::url('comptes/{id}/lien/'), [A::class, 'passwordLink']);
    $router->post(Admin::url('comptes/{id}/suppression/'), [A::class, 'destroy']);
};
