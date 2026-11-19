<?php
declare(strict_types=1);

/*
 * Accueil du back-office. Dans app/routes.php, avant « return $router; » :
 *   (require __DIR__ . '/Admin/routes-tableau-de-bord.php')($router);
 */

use App\Admin\Admin;
use App\Admin\DashboardController;
use App\Core\Router;

return static function (Router $router): void {
    $router->get(Admin::url(), [DashboardController::class, 'index']);
};
