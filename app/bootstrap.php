<?php
declare(strict_types=1);

/*
 * Démarrage commun au site (public/index.php) et aux scripts (scripts/*.php) :
 * chargement automatique des classes App\..., fonctions utilitaires, .env, configuration, journal d'erreurs.
 */

const ROOT = __DIR__ . '/..';

// Extensions dont les briques ont besoin (textes accentués : mb_*). Absente : arrêt clair dès le démarrage,
// plutôt qu'une erreur fatale au premier écran qui l'utilise. Même liste que gabarits/installation-mutualise.
foreach (['mbstring'] as $extension) {
    if (!extension_loaded($extension)) {
        if (PHP_SAPI !== 'cli') {
            http_response_code(500);
            header('Content-Type: text/plain; charset=utf-8');
        }
        echo "Extension PHP manquante : $extension. L'activer (paquet php-$extension, ou panneau de l'hébergeur) puis relancer." . PHP_EOL;
        exit(1);
    }
}

spl_autoload_register(static function (string $class): void {
    if (str_starts_with($class, 'App\\')) {
        $file = __DIR__ . '/' . str_replace('\\', '/', substr($class, 4)) . '.php';
        if (is_file($file)) {
            require $file;
        }
    }
});

require __DIR__ . '/helpers.php';

App\Core\Env::load(ROOT . '/.env');

error_reporting(E_ALL);
ini_set('display_errors', is_local() ? '1' : '0');
ini_set('log_errors', '1');
if (is_dir(ROOT . '/data') && is_writable(ROOT . '/data')) {
    ini_set('error_log', ROOT . '/data/php-errors.log');
}
date_default_timezone_set((string) config('app.timezone', 'Europe/Paris'));

// Production : toute exception non prévue est journalisée et affiche la page d'erreur 500 (jamais de trace à l'écran).
if (!is_local() && PHP_SAPI !== 'cli') {
    set_exception_handler(static function (Throwable $e): void {
        error_log('Exception : ' . $e->getMessage() . ' dans ' . $e->getFile() . ':' . $e->getLine());
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        abort(500);
    });
}

require __DIR__ . '/Langue/fonctions.php';
require __DIR__ . '/Site/navigation-fiches.php';
