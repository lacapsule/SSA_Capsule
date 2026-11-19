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
(require __DIR__ . '/Admin/routes-comptes.php')($router);
(require __DIR__ . '/Admin/routes-journal.php')($router);
(require __DIR__ . '/Agenda/routes-admin.php')($router);
(require __DIR__ . '/Actualites/routes-admin.php')($router);
(require __DIR__ . '/Galerie/routes-admin.php')($router);
$router->get(App\Admin\Admin::url('lieux/export'), [App\Admin\Exports::class, 'lieux']);
$router->get(App\Admin\Admin::url('agenda/export'), [App\Admin\Exports::class, 'agenda']);
(require __DIR__ . '/Sauvegarde/routes.php')($router);
(require __DIR__ . '/Session/routes.php')($router);
// Rubriques des contenus, après les routes fixes de l'administration (crud-declaratif : boucle sur config/ressources.php).
(require __DIR__ . '/Admin/routes-ressources.php')($router);

return $router;
