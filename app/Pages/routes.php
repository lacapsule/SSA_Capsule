<?php
declare(strict_types=1);

use App\Core\Router;
use App\Pages\Pages;

return static function (Router $router): void {
    $router->get('/', static fn () => Pages::afficher('/', 'accueil', ['evenements' => App\Agenda\Agenda::aVenir(3), 'articles' => App\Actualites\Actualites::dernieres(3), 'collectif' => App\Langue\Contenu::traduire('page', '/collectif/', Pages::textes('/collectif/'))]));
    $router->get('/agenda/', [App\Agenda\Agenda::class, 'liste']);
    $router->get('/agenda/{slug}/', [App\Agenda\Agenda::class, 'fiche']);
    $router->get('/lieux-conventionnes/', [App\Lieux\Lieux::class, 'liste']);
    $router->get('/contact/', [App\Contact\Contact::class, 'form']);
    $router->post('/contact/', [App\Contact\Contact::class, 'send']);
    $router->get('/galerie/', [App\Galerie\Galerie::class, 'liste']);
    $router->get('/actualites/', [App\Actualites\Actualites::class, 'liste']);
    $router->get('/actualites/{slug}/', [App\Actualites\Actualites::class, 'fiche']);
    $router->get('/projet/', static fn () => Pages::afficher('/projet/', 'projet'));
    $router->get('/collectif/', static fn () => Pages::afficher('/collectif/', 'collectif'));
};
