<?php
declare(strict_types=1);

use App\Core\Router;

// Table des routes : chemins français seulement (modules/multilingue sert les mêmes sous /br/).
$router = new Router();

(require __DIR__ . '/Site/routes.php')($router);
$router->get('/sitemap.xml', [App\Langue\Sitemap::class, 'afficher']);
(require __DIR__ . '/Pages/routes.php')($router);
(require __DIR__ . '/Reprise/routes.php')($router);
// Administration (modules/authentification-admin, gabarits/tableau-de-bord-php).
require __DIR__ . '/Admin/routes-auth.php';
(require __DIR__ . '/Admin/routes-tableau-de-bord.php')($router);

return $router;
