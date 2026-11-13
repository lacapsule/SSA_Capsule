<?php
declare(strict_types=1);

use App\Core\Router;
use App\Core\View;

// Table des routes : une ligne par adresse. Les contrôleurs vivent dans app/<Domaine>/ (App\Site\..., App\Admin\...).
$router = new Router();

$router->get('/', static function (): void {
    View::render('pages/home', ['title' => config('app.name', 'Accueil')], 'layout');
});

// Exemple de segment nommé : /fiches/12/ appelle le gestionnaire avec $id = '12'.
$router->get('/fiches/{id}/', static function (string $id): void {
    ctype_digit($id) ? View::render('pages/home', ['title' => 'Fiche ' . $id], 'layout') : abort(404);
});

return $router;
