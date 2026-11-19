<?php
declare(strict_types=1);

// Rubrique Agenda du tableau de bord (App\Agenda\AgendaAdmin). Dans app/routes.php, avec les routes fixes de l'admin.

use App\Admin\Admin;
use App\Agenda\AgendaAdmin as A;
use App\Core\Router;

return static function (Router $router): void {
    $router->get(Admin::url('agenda/'), [A::class, 'index']);
    $router->get(Admin::url('agenda/nouveau/'), [A::class, 'create']);
    $router->post(Admin::url('agenda/nouveau/'), [A::class, 'store']);
    $router->get(Admin::url('agenda/{id}/'), [A::class, 'edit']);
    $router->post(Admin::url('agenda/{id}/'), [A::class, 'update']);
    $router->post(Admin::url('agenda/{id}/supprimer/'), [A::class, 'delete']);
};
