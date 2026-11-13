<?php
declare(strict_types=1);

// Contrôleur frontal : toute requête qui ne vise pas un fichier réel de public/ arrive ici (.htaccess ou router.php).
require __DIR__ . '/../app/bootstrap.php';

$path = current_path();
$method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));

/** @var App\Core\Router $router */
$router = require __DIR__ . '/../app/routes.php';
$router->error = static fn (int $status) => abort($status);

// Une seule adresse par contenu : /index.php redirige vers /, et une adresse sans slash final redirige en 301
// vers sa forme avec slash SEULEMENT si aucune route ne répond à la forme sans slash et qu'une route répond à l'autre.
// Pas de liste d'exceptions à tenir : un lien signé (/lettre/confirmer?jeton=...) ou un export (/admin/export.csv)
// déclaré sans slash est servi tel quel. Fichiers (point dans le dernier segment) jamais redirigés.
if ($method === 'GET' || $method === 'HEAD') {
    if ($path === '/index.php') {
        redirect('/', 301);
    }
    if (!str_ends_with($path, '/') && !str_contains(basename($path), '.') && !$router->has($path) && $router->has($path . '/')) {
        $query = (string) ($_SERVER['QUERY_STRING'] ?? '');
        // Barres obliques de tête ramenées à une seule : « //autre-site » ne devient jamais une redirection ouverte.
        redirect('/' . ltrim($path, '/\\') . '/' . ($query !== '' ? '?' . $query : ''), 301);
    }
    // Inverse : route déclarée sans slash (module qui garde ses adresses d'origine), lien écrit avec.
    if ($path !== '/' && str_ends_with($path, '/') && !$router->has($path) && $router->has(rtrim($path, '/'))) {
        $query = (string) ($_SERVER['QUERY_STRING'] ?? '');
        redirect('/' . trim($path, '/\\') . ($query !== '' ? '?' . $query : ''), 301);
    }
}

App\Core\Headers::send($path);

try {
    $router->dispatch($method, $path);
} catch (App\Core\HttpException $e) {
    // Arrêt sans exit() (code testable) : redirection telle quelle, sinon page d'erreur du site au bon statut.
    $e->location !== null ? $e->send() : abort($e->status, $e->getMessage() !== '' ? $e->getMessage() : null);
}
