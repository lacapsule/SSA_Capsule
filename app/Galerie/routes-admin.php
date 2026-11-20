<?php
declare(strict_types=1);

// Rubrique Galerie du tableau de bord (App\Galerie\GalerieAdmin). Dans app/routes.php, avec les routes fixes de l'admin.

use App\Admin\Admin;
use App\Core\Router;
use App\Galerie\GalerieAdmin as G;

return static function (Router $router): void {
    $router->get(Admin::url('galerie/'), [G::class, 'index']);
    $router->post(Admin::url('galerie/'), [G::class, 'enregistrer']);
    $router->post(Admin::url('galerie/envoi/'), [G::class, 'envoyer']);
    $router->post(Admin::url('galerie/deplacer/'), [G::class, 'deplacer']);
    $router->get(Admin::url('photos-a-decrire/'), [App\Galerie\DescriptionsAdmin::class, 'index']);
    $router->post(Admin::url('photos-a-decrire/'), [App\Galerie\DescriptionsAdmin::class, 'enregistrer']);
};
