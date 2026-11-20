<?php
declare(strict_types=1);

/*
 * Outils de développement local, sans dépendance (Linux, macOS, Windows). Appelé par le Makefile, utilisable seul.
 *   php scripts/dev.php serve [--host=127.0.0.1] [--port=8080]   site sur http://127.0.0.1:8080 (base créée si absente)
 *   php scripts/dev.php db      recrée data/dev.sqlite : migrations, database/seed.php s'il existe, contenus du site actuel
 *   php scripts/dev.php init    même chose, seulement si la base ou public/media/ manque (clone neuf)
 *   php scripts/dev.php compte  migrations en attente, puis compte de développement créé ou remis (base SQLite de data/ seulement)
 *   php scripts/dev.php help Makefile [autres.mk]   liste les cibles commentées « ## » (make help)
 *   php scripts/dev.php info    vérifie PHP et les extensions
 *   php scripts/dev.php lint    vérifie la syntaxe de tous les fichiers PHP
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

const MIN_PHP = '8.2.0';
// Extensions chargées à la volée si le php.ini ne les active pas (sans modifier le php.ini).
const LOADABLE = ['pdo_sqlite', 'openssl', 'sodium', 'mbstring'];
const REQUIRED = ['pdo_sqlite', 'pdo_mysql', 'openssl', 'sodium', 'mbstring'];
// Mêmes valeurs que App\Admin\AdminAuth::DEV_EMAIL et DEV_PASSWORD (serve les affiche sans charger l'application).
const DEV_EMAIL = 'dev@exemple.invalid';
const DEV_PASSWORD = 'developpement-local';
const SKIP_DIRS = ['.git', 'vendor', 'node_modules', 'data', '.claude'];

if (PHP_OS_FAMILY === 'Windows' && function_exists('sapi_windows_cp_set')) {
    sapi_windows_cp_set(65001);
}

$root = dirname(__DIR__);
$command = $argv[1] ?? 'serve';
$options = [];
foreach (array_slice($argv, 2) as $arg) {
    if (preg_match('/^--([a-z]+)=(.*)$/', $arg, $m) === 1) {
        $options[$m[1]] = $m[2];
    }
}

if (in_array($command, ['db', 'init', 'compte'], true)) {
    relaunchWithExtensions(['pdo_sqlite', 'sodium']);
}

exit(match ($command) {
    'serve' => serve($root, $options),
    'db' => database($root),
    'init' => init($root),
    'compte' => compteDev($root),
    'info' => info(),
    'help' => aide(array_slice($argv, 2)),
    'lint' => lint($root),
    default => fail("Commande inconnue : $command (serve, db, init, help, info ou lint)"),
});

/** Options -d extension=... pour les extensions disponibles mais non activées. @param list<string> $wanted */
function extensionFlags(array $wanted): string
{
    $flags = '';
    foreach ($wanted as $ext) {
        if (!extension_loaded($ext)) {
            $flags .= ' -d extension=' . $ext;
        }
    }
    return $flags;
}

/** @param list<string> $wanted */
function relaunchWithExtensions(array $wanted): void
{
    $flags = extensionFlags($wanted);
    if ($flags === '' || getenv('APP_DEV_RELAUNCHED') === '1') {
        return;
    }
    putenv('APP_DEV_RELAUNCHED=1');
    global $argv;
    passthru(escapeshellarg(PHP_BINARY) . $flags . ' ' . implode(' ', array_map('escapeshellarg', $argv)), $code);
    exit($code);
}

/** @param array<string, string> $options */
function serve(string $root, array $options): int
{
    $host = $options['host'] ?? '127.0.0.1';
    $port = $options['port'] ?? '8080';
    if (filter_var($host, FILTER_VALIDATE_IP) === false && $host !== 'localhost') {
        return fail("Hôte invalide : $host");
    }
    if (!ctype_digit($port) || (int) $port < 1024 || (int) $port > 65535) {
        return fail("Port invalide : $port (1024 à 65535)");
    }
    foreach (['init', 'compte'] as $etape) {
        passthru(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(__FILE__) . ' ' . $etape, $code);
        if ($code !== 0) {
            return $code;
        }
    }
    // Poste de développement : APP_ENV=local (e-mails écrits dans data/mail.log, erreurs affichées, connexion au
    // back-office sans double authentification) si ni l'environnement ni le .env ne le règlent déjà.
    $envFile = "$root/.env";
    $dejaRegle = getenv('APP_ENV') !== false || (is_file($envFile) && preg_match('/^\s*APP_ENV\s*=/m', (string) file_get_contents($envFile)) === 1);
    if (!$dejaRegle) {
        putenv('APP_ENV=local');
    }
    $url = "http://$host:$port/";
    fwrite(STDOUT, PHP_EOL . '  Serveur de développement' . PHP_EOL . "  Adresse : $url" . PHP_EOL);
    fwrite(STDOUT, '  E-mails : data/mail.log (sans serveur d\'envoi réglé)' . PHP_EOL);
    fwrite(STDOUT, "  Administration : {$url}admin/ (formulaire prérempli)" . PHP_EOL
        . '    e-mail       ' . DEV_EMAIL . PHP_EOL . '    mot de passe ' . DEV_PASSWORD . PHP_EOL . '  Arrêt   : Ctrl+C' . PHP_EOL . PHP_EOL);
    // Tailles d'envoi du tableau de bord, comme en production (public/.user.ini, que le serveur intégré ne lit pas).
    $cmd = escapeshellarg(PHP_BINARY) . extensionFlags(LOADABLE) . ' -d upload_max_filesize=64M -d post_max_size=200M -d max_file_uploads=40 -S ' . escapeshellarg("$host:$port")
        . ' -t ' . escapeshellarg("$root/public") . ' ' . escapeshellarg("$root/public/router.php");
    passthru($cmd, $code);
    return $code;
}

function database(string $root): int
{
    if (!extension_loaded('pdo_sqlite')) {
        return fail('Extension pdo_sqlite introuvable : vérifiez extension_dir dans le php.ini.');
    }
    is_dir("$root/data") || mkdir("$root/data", 0775, true);
    foreach (['dev.sqlite', 'dev.sqlite-wal', 'dev.sqlite-shm'] as $file) {
        is_file("$root/data/$file") && unlink("$root/data/$file");
    }
    $_ENV['DB_DSN'] = 'sqlite:' . $root . '/data/dev.sqlite';
    require $root . '/app/bootstrap.php';
    $applied = App\Core\Migrator::run();
    if (is_file($root . '/database/seed.php')) {
        require $root . '/database/seed.php';
    }
    fwrite(STDOUT, 'Base de dev recréée (' . (implode(', ', $applied) ?: 'aucune migration') . ') : data/dev.sqlite' . PHP_EOL);
    // DB_DSN du processus prime sur le .env (App\Core\Env) : l'import vise toujours la base de dev.
    putenv('DB_DSN=sqlite:' . $root . '/data/dev.sqlite');
    passthru(escapeshellarg(PHP_BINARY) . extensionFlags(LOADABLE) . ' ' . escapeshellarg($root . '/scripts/importer-contenus.php'), $code);
    return $code;
}

/** Clone neuf : data/ et public/media/ ne sont pas versionnés, la base se crée avec les contenus. Une base existante reste. */
function init(string $root): int
{
    if (is_file("$root/data/dev.sqlite") && is_dir("$root/public/media")) {
        fwrite(STDOUT, 'Base de dev et médias présents : rien à faire (make db pour tout recréer).' . PHP_EOL);
        return 0;
    }
    fwrite(STDOUT, 'Base de dev ou médias absents : création avec les contenus du site actuel...' . PHP_EOL);
    return database($root);
}

/**
 * Compte administrateur du poste de développement, mot de passe connu (affiché par make dev, prérempli sur la page de
 * connexion). Écrit seulement dans une base SQLite de data/ : jamais dans une base distante réglée par le .env.
 * AdminAuth le refuse hors du serveur intégré de PHP sur le poste local.
 */
function compteDev(string $root): int
{
    require $root . '/app/bootstrap.php';
    $dsn = (string) ((require $root . '/config/database.php')['dsn'] ?? '');
    $base = str_starts_with($dsn, 'sqlite:') ? realpath(substr($dsn, 7)) : false;
    $data = realpath("$root/data");
    if ($base === false || $data === false || !str_starts_with($base, $data . DIRECTORY_SEPARATOR)) {
        fwrite(STDOUT, 'Compte de développement non créé : la base réglée n\'est pas une base SQLite de data/.' . PHP_EOL);
        return 0;
    }
    // Migrations ajoutées depuis la création de la base : appliquées sans recréer la base (make db efface tout).
    foreach (App\Core\Migrator::run() as $m) {
        fwrite(STDOUT, "Migration appliquée à la base de dev : $m" . PHP_EOL);
    }
    if (DEV_EMAIL !== App\Admin\AdminAuth::DEV_EMAIL || DEV_PASSWORD !== App\Admin\AdminAuth::DEV_PASSWORD) {
        return fail('Compte de développement : valeurs différentes entre scripts/dev.php et AdminAuth.');
    }
    $hash = (string) App\Core\Db::run('SELECT password_hash FROM admins WHERE email = ?', [DEV_EMAIL])->fetchColumn();
    if ($hash === '') {
        App\Core\Db::run("INSERT INTO admins (email, name, password_hash, role, created_at) VALUES (?, 'Compte de développement', ?, 'admin', ?)",
            [DEV_EMAIL, password_hash(DEV_PASSWORD, defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_DEFAULT), now()]);
    } else {
        // Mot de passe remis s'il a changé, blocage levé : le compte affiché par make dev marche toujours.
        $nouveau = password_verify(DEV_PASSWORD, $hash) ? $hash : password_hash(DEV_PASSWORD, defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_DEFAULT);
        App\Core\Db::run("UPDATE admins SET password_hash = ?, role = 'admin', failed_logins = 0, locked_until = NULL WHERE email = ?", [$nouveau, DEV_EMAIL]);
    }
    return 0;
}

/** Cibles « nom: ## description » des fichiers make donnés (Makefile et fichiers inclus), dans l'ordre. @param list<string> $fichiers */
function aide(array $fichiers): int
{
    $vues = [];
    foreach ($fichiers ?: ['Makefile'] as $f) {
        foreach (is_file($f) ? (file($f, FILE_IGNORE_NEW_LINES) ?: []) : [] as $l) {
            if (preg_match('/^([a-z][a-z0-9-]*):.*## (.*)$/', $l, $m) === 1 && !isset($vues[$m[1]])) {
                $vues[$m[1]] = true;
                fwrite(STDOUT, sprintf("  %-11s %s\n", $m[1], $m[2]));
            }
        }
    }
    return 0;
}

function info(): int
{
    $ok = version_compare(PHP_VERSION, MIN_PHP, '>=');
    line($ok, 'PHP ' . PHP_VERSION . ' (minimum ' . MIN_PHP . ')');
    foreach (REQUIRED as $ext) {
        $loaded = extension_loaded($ext);
        $loadable = !$loaded && in_array($ext, LOADABLE, true);
        line($loaded || $loadable, "Extension $ext" . ($loadable ? ' (chargée automatiquement par make dev)' : ''));
        $ok = $ok && ($loaded || $loadable);
    }
    fwrite(STDOUT, PHP_EOL . ($ok ? 'Environnement prêt.' : 'Il manque des éléments (voir ci-dessus).') . PHP_EOL);
    return $ok ? 0 : 1;
}

function lint(string $root): int
{
    $it = new RecursiveIteratorIterator(new RecursiveCallbackFilterIterator(
        new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
        static fn (SplFileInfo $f): bool => !($f->isDir() && in_array($f->getFilename(), SKIP_DIRS, true)),
    ));
    $files = 0;
    $errors = 0;
    foreach ($it as $file) {
        if (!$file->isFile() || $file->getExtension() !== 'php') {
            continue;
        }
        $files++;
        $out = [];
        exec(escapeshellarg(PHP_BINARY) . ' -l ' . escapeshellarg($file->getPathname()) . ' 2>&1', $out, $code);
        if ($code !== 0) {
            $errors++;
            fwrite(STDERR, implode(PHP_EOL, $out) . PHP_EOL);
        }
    }
    fwrite(STDOUT, "$files fichier(s) PHP vérifié(s), $errors erreur(s)." . PHP_EOL);
    return $errors === 0 ? 0 : 1;
}

function line(bool $ok, string $label): void
{
    fwrite(STDOUT, ($ok ? '  [ok]     ' : '  [manque] ') . $label . PHP_EOL);
}

function fail(string $message): int
{
    fwrite(STDERR, $message . PHP_EOL);
    return 1;
}
