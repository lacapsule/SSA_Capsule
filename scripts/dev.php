<?php
declare(strict_types=1);

/*
 * Outils de développement local, sans dépendance (Linux, macOS, Windows). Appelé par le Makefile, utilisable seul.
 *   php scripts/dev.php serve [--host=127.0.0.1] [--port=8080]   site sur http://127.0.0.1:8080 (base créée si absente)
 *   php scripts/dev.php db      recrée data/dev.sqlite : migrations, puis database/seed.php s'il existe
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

if ($command === 'db') {
    relaunchWithExtensions(['pdo_sqlite', 'sodium']);
}

exit(match ($command) {
    'serve' => serve($root, $options),
    'db' => database($root),
    'info' => info(),
    'lint' => lint($root),
    default => fail("Commande inconnue : $command (serve, db, info ou lint)"),
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
    if (!is_file("$root/data/dev.sqlite")) {
        fwrite(STDOUT, 'Base de dev absente : création (make db)...' . PHP_EOL);
        passthru(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(__FILE__) . ' db', $code);
        if ($code !== 0) {
            return $code;
        }
    }
    $url = "http://$host:$port/";
    fwrite(STDOUT, PHP_EOL . '  Serveur de développement' . PHP_EOL . "  Adresse : $url" . PHP_EOL);
    fwrite(STDOUT, '  E-mails : data/mail.log (sans serveur d\'envoi réglé)' . PHP_EOL . '  Arrêt   : Ctrl+C' . PHP_EOL . PHP_EOL);
    $cmd = escapeshellarg(PHP_BINARY) . extensionFlags(LOADABLE) . ' -S ' . escapeshellarg("$host:$port")
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
