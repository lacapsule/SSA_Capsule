<?php
declare(strict_types=1);

/*
 * Écran du journal (administrateurs). Dans app/routes.php, avant « return $router; » :
 *   (require __DIR__ . '/Admin/routes-journal.php')($router);
 */

use App\Admin\Admin;
use App\Admin\AuditController;
use App\Core\Router;

return static function (Router $router): void {
    $router->get(Admin::url('journal/'), [AuditController::class, 'index']);
};
