<?php
declare(strict_types=1);

/*
 * Routes du module. Dans app/routes.php, avant « return $router; » :
 *   (require __DIR__ . '/Site/routes.php')($router);
 */
use App\Site\Discovery;
use App\Site\ErrorPage;
use App\Core\Router;

return static function (Router $router): void {
    $router->notFound = [ErrorPage::class, 'notFound'];
    $router->get('/robots.txt', [Discovery::class, 'robots']);
    $router->get('/sitemap.xml', [Discovery::class, 'sitemap']);
    $router->get('/llms.txt', [Discovery::class, 'llms']);
};
