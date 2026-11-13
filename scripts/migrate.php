<?php
declare(strict_types=1);

/*
 * Applique les migrations de migrations/ sur la base du .env (DB_DSN, DB_USER, DB_PASSWORD).
 * N'efface rien : chaque migration n'est appliquée qu'une fois (table schema_migrations), le script se rejoue sans risque.
 *   php scripts/migrate.php
 * Code de sortie : 0 si la base est à jour, 1 en cas d'échec (message sur la sortie d'erreur).
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require dirname(__DIR__) . '/app/bootstrap.php';

$fail = static function (string $message): never {
    fwrite(STDERR, "Échec : $message" . PHP_EOL);
    exit(1);
};

$dsn = (string) ($_ENV['DB_DSN'] ?? '');
if ($dsn === '' && !is_local()) {
    $fail('DB_DSN est vide dans le .env (attendu : mysql:host=...;dbname=...;charset=utf8mb4). Rien n\'a été modifié.');
}
if ($dsn !== '' && !str_starts_with($dsn, 'mysql:') && !str_starts_with($dsn, 'sqlite:')) {
    $fail('DB_DSN doit commencer par mysql: (MySQL ou MariaDB) ou sqlite:.');
}
if (str_starts_with($dsn, 'mysql:') && !extension_loaded('pdo_mysql')) {
    $fail('extension pdo_mysql absente de ce PHP.');
}

try {
    $pdo = App\Core\Db::pdo();
    fwrite(STDOUT, 'Base : ' . $pdo->getAttribute(PDO::ATTR_DRIVER_NAME) . ' ' . $pdo->getAttribute(PDO::ATTR_SERVER_VERSION) . PHP_EOL);
    $applied = App\Core\Migrator::run();
} catch (Throwable $e) {
    $fail($e->getMessage());
}

$total = count(glob(dirname(__DIR__) . '/migrations/*.sql') ?: []);
if ($applied === []) {
    fwrite(STDOUT, "Base déjà à jour : $total migration(s), aucune à appliquer." . PHP_EOL);
} else {
    foreach ($applied as $name) {
        fwrite(STDOUT, "  appliquée : $name" . PHP_EOL);
    }
    fwrite(STDOUT, count($applied) . " migration(s) appliquée(s) ; base à jour ($total au total)." . PHP_EOL);
}
exit(0);
