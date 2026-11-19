<?php
declare(strict_types=1);

// Routes du module : dans app/routes.php, (require __DIR__ . '/Admin/routes-ressources.php')($router); APRÈS les routes fixes de l'admin
// (/admin/comptes, /admin/pages…) : une route par clé de ressource, pour ne capturer aucun autre chemin.
use App\Admin\ResourceController as R;
use App\Admin\Resources;
use App\Core\Router;

return static function (Router $router): void {
    $router->get('/admin/adresses', [R::class, 'addresses']);
    foreach (array_keys(Resources::all()) as $resource) {
        $router->get("/admin/$resource", static fn () => R::index($resource));
        $router->get("/admin/$resource/nouveau", static fn () => R::create($resource));
        $router->post("/admin/$resource", static fn () => R::store($resource));
        $router->get("/admin/$resource/{id}", static fn (string $id) => R::edit($resource, $id));
        $router->post("/admin/$resource/{id}", static fn (string $id) => R::update($resource, $id));
        $router->post("/admin/$resource/{id}/suppression", static fn (string $id) => R::destroy($resource, $id));
        $router->post("/admin/$resource/{id}/deplacer", static fn (string $id) => R::move($resource, $id));
    }
};
