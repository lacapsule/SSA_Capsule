<?php
declare(strict_types=1);

use App\Core\Router;
use App\Reprise\Reprise;

return static function (Router $router): void {
    $router->get('/article/{numero}', [Reprise::class, 'article']);
};
