<?php
declare(strict_types=1);

// Rubrique Actualités du tableau de bord (App\Actualites\ActualitesAdmin). Dans app/routes.php, avec les routes fixes de l'admin.

use App\Actualites\ActualitesAdmin as A;
use App\Admin\Admin;
use App\Core\Router;

return static function (Router $router): void {
    $router->get(Admin::url('actualites/'), [A::class, 'index']);
    $router->get(Admin::url('actualites/nouveau/'), [A::class, 'create']);
    $router->post(Admin::url('actualites/nouveau/'), [A::class, 'store']);
    $router->get(Admin::url('actualites/{id}/'), [A::class, 'edit']);
    $router->post(Admin::url('actualites/{id}/'), [A::class, 'update']);
    $router->post(Admin::url('actualites/{id}/supprimer/'), [A::class, 'delete']);
};
