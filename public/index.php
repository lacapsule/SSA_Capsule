<?php
declare(strict_types=1);

// Contrôleur frontal : celui de gabarits/socle-php, avec deux ajouts de ce gabarit :
// adresse unique en 301 (Redirects::canonical, config/redirections.tsv) et page introuvable dans la mise en page du site.
require __DIR__ . '/../app/bootstrap.php';

use App\Site\Redirects;

$path = current_path();
$method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));

/** @var App\Core\Router $router */
$router = require __DIR__ . '/../app/routes.php';
$router->error = static fn (int $status) => abort($status);
// Langue lue dans l'URL (/br/...) : la suite reçoit le chemin interne, les routes sont déclarées en français.
$path = App\Langue\Langue::demarrer($path, $router, $method);

if ($method === 'GET' || $method === 'HEAD') {
    if ($path === '/index.php') {
        redirect('/', 301);
    }
    // Redirections du fichier et adresse unique des pages connues (celles de modules/pages-sections-markdown si présent).
    $pages = class_exists(App\Site\SiteController::class) && is_file(ROOT . '/config/pages.php')
        ? App\Site\SiteController::pages()
        : (array) App\Site\Site::get('pages', []);
    $target = Redirects::canonical($path, static fn (string $p): bool => isset($pages[$p]));
    if ($target !== null) {
        redirect($target, 301);
    }
    // Slash final, dans un sens ou dans l'autre, seulement vers une route qui existe (voir socle-php).
    $query = (string) ($_SERVER['QUERY_STRING'] ?? '');
    $query = $query !== '' ? '?' . $query : '';
    if (!str_ends_with($path, '/') && !str_contains(basename($path), '.') && !$router->has($path) && $router->has($path . '/')) {
        redirect('/' . ltrim($path, '/\\') . '/' . $query, 301);
    }
    if ($path !== '/' && str_ends_with($path, '/') && !$router->has($path) && $router->has(rtrim($path, '/'))) {
        redirect('/' . trim($path, '/\\') . $query, 301);
    }
}

App\Core\Headers::send($path);

try {
    $router->dispatch($method, $path);
} catch (App\Core\HttpException $e) {
    // Redirection telle quelle ; 404 public dans la mise en page du site ; autres statuts : page d'erreur du socle.
    if ($e->location !== null) {
        $e->send();
    } elseif ($e->status === 404 && !str_starts_with($path, '/admin/')) {
        App\Site\ErrorPage::notFound();
    } else {
        abort($e->status, $e->getMessage() !== '' ? $e->getMessage() : null);
    }
}
