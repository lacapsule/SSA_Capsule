<?php
declare(strict_types=1);

/*
 * Tâche planifiée de l'hébergeur (panneau d'administration), par exemple toutes les 15 minutes :
 *   php <chemin du site>/scripts/cron.php
 * Chaque tâche est indépendante : l'échec de l'une est journalisé et n'empêche pas les suivantes.
 * Un verrou (data/cron.lock) évite deux exécutions simultanées quand une tâche dure plus que l'intervalle.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require dirname(__DIR__) . '/app/bootstrap.php';

is_dir(ROOT . '/data') || mkdir(ROOT . '/data', 0775, true);
$lock = fopen(ROOT . '/data/cron.lock', 'c');
if ($lock === false || !flock($lock, LOCK_EX | LOCK_NB)) {
    fwrite(STDOUT, date('Y-m-d H:i') . ' : exécution précédente encore en cours, rien à faire.' . PHP_EOL);
    exit(0);
}

/** @var array<string, callable(): string> $tasks nom => tâche, qui renvoie un compte rendu court */
$tasks = [
    // Exemples, selon les modules installés :
    // 'e-mails différés' => static fn (): string => App\Core\Outbox::flush() . ' envoyé(s)',
    // 'liens expirés' => static fn (): string => App\Admin\AdminAuth::purgeResetTokens() . ' purgé(s)',
    // 'limites de débit' => static fn (): string => App\Core\FormGuard::purge() . ' trace(s) purgée(s)',
    'journal PHP' => static function (): string {
        // Journal d'erreurs gardé sous 5 Mo : l'ancien est renommé (une seule génération conservée).
        $log = ROOT . '/data/php-errors.log';
        if (is_file($log) && filesize($log) > 5 * 1024 * 1024) {
            rename($log, $log . '.1');
            return 'archivé';
        }
        return 'rien à faire';
    },
];

$report = [];
$code = 0;
foreach ($tasks as $name => $task) {
    try {
        $report[] = "$name : " . $task();
    } catch (Throwable $e) {
        error_log("Tâche planifiée « $name » : " . $e->getMessage());
        $report[] = "$name : échec (voir le journal)";
        $code = 1;
    }
}

fwrite(STDOUT, date('Y-m-d H:i') . ' ' . implode(' ; ', $report) . PHP_EOL);
flock($lock, LOCK_UN);
fclose($lock);
exit($code);
