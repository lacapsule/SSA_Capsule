<?php
declare(strict_types=1);

use App\Core\Router;
use App\Pages\Pages;

return static function (Router $router): void {
    $router->get('/', static fn () => Pages::afficher('/', 'accueil'));
    $router->get('/projet/', static fn () => Pages::afficher('/projet/', 'projet'));
    $router->get('/collectif/', static fn () => Pages::afficher('/collectif/', 'collectif'));
};
