<?php
declare(strict_types=1);

use App\Core\Router;

// Table des routes : chemins français seulement (modules/multilingue sert les mêmes sous /br/).
$router = new Router();

(require __DIR__ . '/Site/routes.php')($router);
$router->get('/sitemap.xml', [App\Langue\Sitemap::class, 'afficher']);
(require __DIR__ . '/Pages/routes.php')($router);

return $router;
