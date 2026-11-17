<?php
declare(strict_types=1);

use App\Core\Router;
use App\Pages\Pages;

return static function (Router $router): void {
    $router->get('/', static fn () => Pages::afficher('/', 'accueil', ['evenements' => App\Agenda\Agenda::aVenir(3), 'articles' => App\Actualites\Actualites::dernieres(3), 'collectif' => App\Langue\Contenu::traduire('page', '/projet/', Pages::textes('/projet/'))]));
    $router->get('/agenda/', [App\Agenda\Agenda::class, 'liste']);
    $router->get('/agenda/{slug}/', [App\Agenda\Agenda::class, 'fiche']);
    $router->get('/agenda/{slug}/evenement.ics', [App\Agenda\Ics::class, 'evenement']);
    $router->get('/agenda.ics', [App\Agenda\Ics::class, 'calendrier']);
    $router->get('/lieux-conventionnes/', [App\Lieux\Lieux::class, 'liste']);
    $router->get('/lieux-conventionnes/carte/', [App\Lieux\Lieux::class, 'carte']);
    $router->get('/contact/', [App\Contact\Contact::class, 'form']);
    $router->post('/contact/', [App\Contact\Contact::class, 'send']);
    $router->get('/galerie/', [App\Galerie\Galerie::class, 'liste']);
    $router->get('/actualites/', [App\Actualites\Actualites::class, 'liste']);
    $router->get('/actualites/{slug}/', [App\Actualites\Actualites::class, 'fiche']);
    $router->get('/projet/', static fn () => Pages::afficher('/projet/', 'projet', ['scripts' => ['/assets/js/video-lecture.js'], 'video' => App\Actualites\Actualites::video((string) App\Site\Site::get('projet_video', ''))]));
    $router->get('/mentions-legales/', static fn () => Pages::afficher('/mentions-legales/', 'mentions-legales'));
    $router->get('/confidentialite/', static fn () => Pages::afficher('/confidentialite/', 'confidentialite'));
    $router->get('/accessibilite/', static fn () => Pages::afficher('/accessibilite/', 'accessibilite'));
    $router->get('/plan-du-site/', static fn () => Pages::afficher('/plan-du-site/', 'plan-du-site', ['articles' => App\Actualites\Actualites::dernieres(500), 'evenements' => array_merge(App\Agenda\Agenda::aVenir(), App\Agenda\Agenda::passes())]));
};
