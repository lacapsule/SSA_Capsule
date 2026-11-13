<?php
declare(strict_types=1);

// Serveur de développement (php -S) : sert les fichiers réels de public/, le reste passe par index.php, comme le .htaccess.
// Les fichiers cachés (.htaccess, .user.ini) et les .php autres que index.php ne sont jamais servis tels quels.
$path = (string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$file = realpath(__DIR__ . rawurldecode($path));
if ($file !== false && is_file($file) && str_starts_with($file, __DIR__ . DIRECTORY_SEPARATOR)
    && !str_ends_with($file, '.php') && !str_starts_with(basename($file), '.')) {
    return false;
}
require __DIR__ . '/index.php';
