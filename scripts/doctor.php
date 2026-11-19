<?php
declare(strict_types=1);

/*
 * Diagnostic d'une installation du socle PHP, sur le serveur : [ OK  ] tout va bien, [AVERT] à surveiller, [ÉCHEC] à corriger,
 * chaque point avec un conseil. Code de sortie 1 s'il reste un échec : à lancer avant et après chaque mise en ligne.
 *
 *   php scripts/doctor.php            diagnostic local
 *   php scripts/doctor.php --reseau   tente aussi de télécharger .env et la base depuis APP_URL (cela doit échouer)
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require dirname(__DIR__) . '/app/bootstrap.php';

$reseau = in_array('--reseau', $argv, true);
$couleur = getenv('NO_COLOR') === false && function_exists('stream_isatty') && stream_isatty(STDOUT);
$compte = ['ok' => 0, 'avert' => 0, 'echec' => 0];
$point = static function (string $niveau, string $texte, string $conseil = '') use (&$compte, $couleur): void {
    $compte[$niveau]++;
    [$etiquette, $code] = ['ok' => ['[ OK  ]', '32'], 'avert' => ['[AVERT]', '33'], 'echec' => ['[ÉCHEC]', '31']][$niveau];
    echo '  ' . ($couleur ? "\033[{$code}m$etiquette\033[0m" : $etiquette) . ' ' . $texte . PHP_EOL;
    if ($conseil !== '' && $niveau !== 'ok') {
        echo '          -> ' . $conseil . PHP_EOL;
    }
};
$taille = static fn (float $o): string => $o >= 1073741824 ? number_format($o / 1073741824, 1, ',', ' ') . ' Go' : number_format($o / 1048576, 1, ',', ' ') . ' Mo';

$env = (string) ($_ENV['APP_ENV'] ?? 'production');
$production = !in_array($env, ['local', 'test'], true);
echo 'Diagnostic de ' . realpath(ROOT) . ' (APP_ENV=' . $env . ')' . PHP_EOL;

// PHP et extensions
version_compare(PHP_VERSION, '8.2.0', '>=')
    ? $point('ok', 'PHP ' . PHP_VERSION)
    : $point('echec', 'PHP ' . PHP_VERSION . ' : 8.2 minimum', 'Choisir PHP 8.3 dans le panneau de l\'hébergeur.');
$requises = ['pdo', 'mbstring', 'json', 'sodium', 'fileinfo', 'zlib'];
$conseillees = ['intl', 'sqlite3'];
$manquent = array_values(array_filter($requises, static fn (string $e): bool => !extension_loaded($e)));
$manquent === []
    ? $point('ok', 'Extensions : ' . implode(', ', $requises))
    : $point('echec', 'Extensions manquantes : ' . implode(', ', $manquent), 'À cocher dans le panneau de l\'hébergeur (rubrique PHP).');
$absentes = array_values(array_filter($conseillees, static fn (string $e): bool => !extension_loaded($e)));
if ($absentes !== []) {
    $point('avert', 'Extensions conseillées absentes : ' . implode(', ', $absentes));
}

// .env et clés
$fichierEnv = ROOT . '/.env';
if (!is_file($fichierEnv)) {
    $point($production ? 'echec' : 'avert', '.env absent', 'cp .env.example .env, puis le compléter (ou l\'assistant de gabarits/installation-mutualise).');
} else {
    $droits = fileperms($fichierEnv) & 0o777;
    ($droits & 0o077) === 0
        ? $point('ok', sprintf('.env présent, droits %o', $droits))
        : $point('avert', sprintf('.env lisible par d\'autres comptes (droits %o)', $droits), 'chmod 600 .env');
}
if (!in_array($env, ['production', 'local', 'test'], true)) {
    $point('echec', "APP_ENV=$env inconnu", 'Valeurs : production, local.');
}
$appKey = (string) ($_ENV['APP_KEY'] ?? '');
if (preg_match('/^[0-9a-f]{64}$/', $appKey) === 1) {
    $point('ok', 'APP_KEY présente (64 caractères hexadécimaux)');
} else {
    $point($production ? 'echec' : 'avert', $appKey === '' ? 'APP_KEY vide' : 'APP_KEY mal formée',
        'php -r "echo bin2hex(random_bytes(32));" puis la garder aussi hors du serveur. Si des données ont été chiffrées, retrouver l\'ancienne.');
}
if (is_file(ROOT . '/app/Sauvegarde/Sauvegarde.php')) {
    $backup = (string) ($_ENV['BACKUP_KEY'] ?? '');
    if ($backup === '') {
        $point($production ? 'echec' : 'avert', 'BACKUP_KEY vide : sauvegardes refusées en production', 'php scripts/sauvegarde.php cle --ecrire');
    } elseif (preg_match('/^[0-9a-f]{64}$/', $backup) !== 1) {
        $point('echec', 'BACKUP_KEY mal formée (64 caractères hexadécimaux attendus)');
    } elseif (hash_equals($appKey, $backup)) {
        $point('echec', 'BACKUP_KEY identique à APP_KEY', 'Deux clés différentes, chacune pour un seul usage.');
    } else {
        $point('ok', 'BACKUP_KEY présente, différente d\'APP_KEY');
    }
}
$url = (string) ($_ENV['APP_URL'] ?? '');
if ($production) {
    str_starts_with($url, 'https://')
        ? $point('ok', "APP_URL en https ($url)")
        : $point('echec', 'APP_URL n\'est pas en https (' . ($url !== '' ? $url : 'vide') . ')', 'Adresse publique complète en https:// (liens envoyés par e-mail).');
}

// Dossiers
foreach (['data', 'data/sauvegardes'] as $d) {
    $chemin = ROOT . '/' . $d;
    if (!is_dir($chemin)) {
        continue;
    }
    is_writable($chemin)
        ? $point('ok', "Dossier $d modifiable par PHP")
        : $point('echec', "Dossier $d non modifiable par PHP", 'Droits 750 (propriétaire : le compte qui exécute PHP).');
}
if (is_file(ROOT . '/data/installation.jeton') && is_file(ROOT . '/.env')) {
    $point('avert', 'data/installation.jeton encore présent alors que le site est configuré', 'Le supprimer.');
}

// Base de données
try {
    $pdo = App\Core\Db::pdo();
    $moteur = (string) $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    $point('ok', 'Base joignable (' . $moteur . ' ' . $pdo->getAttribute(PDO::ATTR_SERVER_VERSION) . ')');
    if ($moteur === 'sqlite') {
        $mode = strtolower((string) $pdo->query('PRAGMA journal_mode')->fetchColumn());
        $mode === 'wal'
            ? $point('ok', 'SQLite en mode WAL')
            : $point('avert', "SQLite en mode $mode", 'WAL recommandé, sauf dossier réseau (NFS).');
        $integrite = (string) $pdo->query('PRAGMA quick_check')->fetchColumn();
        $integrite === 'ok' ? $point('ok', 'Intégrité SQLite (quick_check)') : $point('echec', 'Intégrité SQLite : ' . $integrite, 'Restaurer la dernière sauvegarde vérifiée.');
    }
    $faites = [];
    try {
        $faites = $pdo->query('SELECT name FROM schema_migrations')->fetchAll(PDO::FETCH_COLUMN);
    } catch (Throwable) {
    }
    $attente = array_values(array_diff(array_map('basename', glob(ROOT . '/migrations/*.sql') ?: []), $faites));
    $attente === []
        ? $point('ok', 'Migrations à jour (' . count($faites) . ')')
        : $point('echec', count($attente) . ' migration(s) en attente : ' . implode(', ', array_slice($attente, 0, 3)), 'php scripts/migrate.php');
} catch (Throwable $e) {
    $point('echec', 'Base inaccessible : ' . $e->getMessage(), 'Vérifier DB_DSN, DB_USER, DB_PASSWORD et les droits de data/.');
}

// Espace disque et journal
$libre = @disk_free_space(ROOT);
if ($libre !== false) {
    $libre < 500 * 1048576
        ? $point('avert', 'Espace disque libre : ' . $taille($libre), 'Peu de place pour les sauvegardes.')
        : $point('ok', 'Espace disque libre : ' . $taille($libre));
}
$journal = ROOT . '/data/php-errors.log';
if (is_file($journal)) {
    $t = (int) filesize($journal);
    $recent = time() - (int) filemtime($journal) < 86400;
    $point($recent && $t > 0 ? 'avert' : 'ok', 'Journal d\'erreurs PHP : ' . $taille($t) . ($recent ? ', écrit dans les dernières 24 h' : ''),
        'Lire data/php-errors.log.');
}

// Sauvegardes (modules/sauvegarde-chiffree)
if (class_exists(App\Sauvegarde\Sauvegarde::class)) {
    try {
        $s = App\Sauvegarde\Sauvegarde::depuisConfig();
        $derniere = $s->plusRecente();
        if ($derniere === null) {
            $point($production ? 'echec' : 'avert', 'Aucune sauvegarde', 'Tâche planifiée : php scripts/sauvegarde.php creer --type=quotidienne');
        } else {
            $jours = (time() - (int) strtotime($derniere['date'])) / 86400;
            $max = (int) config('sauvegarde.alerte_sauvegarde_jours', 2);
            $jours > $max
                ? $point('echec', sprintf('Dernière sauvegarde il y a %d jour(s)', (int) $jours), 'La tâche planifiée ne tourne plus ?')
                : $point('ok', 'Dernière sauvegarde : ' . $derniere['date'] . ($derniere['chiffre'] ? ', chiffrée' : ', NON chiffrée'));
        }
        $v = $s->derniereVerification();
        if ($v === null) {
            $point('avert', 'Aucune vérification de sauvegarde enregistrée', 'php scripts/sauvegarde.php verifier (et chaque semaine).');
        } elseif (!$v['ok']) {
            $point('echec', 'Dernière vérification en échec (' . $v['fichier'] . ')', implode(' ; ', $v['problemes']));
        } else {
            $point('ok', 'Dernière vérification réussie le ' . $v['date']);
        }
    } catch (Throwable $e) {
        $point('echec', 'Sauvegardes : ' . $e->getMessage());
    }
}

// Exposition web : .env et base ne doivent jamais se télécharger.
if ($reseau && preg_match('#^https?://#', $url) === 1) {
    $exposes = [];
    $joignable = false;
    foreach (['/.env', '/../.env', '/data/app.sqlite', '/data/', '/migrations/', '/config/app.php'] as $chemin) {
        $ctx = stream_context_create(['http' => ['method' => 'GET', 'timeout' => 4, 'ignore_errors' => true, 'follow_location' => 0]]);
        $corps = @file_get_contents(rtrim($url, '/') . $chemin, false, $ctx, 0, 2048);
        $statut = 0;
        foreach ($http_response_header ?? [] as $h) {
            if (preg_match('#^HTTP/\S+\s+(\d{3})#', $h, $m) === 1) {
                $statut = (int) $m[1];
            }
        }
        if ($statut === 0) {
            continue;
        }
        $joignable = true;
        if ($statut === 200 && is_string($corps) && (str_contains($corps, 'APP_KEY') || str_starts_with($corps, 'SQLite format 3') || str_contains($corps, 'Index of') || str_contains($corps, '<?php'))) {
            $exposes[] = $chemin;
        }
    }
    if (!$joignable) {
        $point('avert', "Exposition non testée : $url ne répond pas");
    } elseif ($exposes !== []) {
        $point('echec', 'Téléchargeables depuis le web : ' . implode(', ', $exposes), 'URGENT : la racine web doit être public/ ; vérifier les .htaccess.');
    } else {
        $point('ok', '.env, base, migrations et configuration non accessibles depuis le web');
    }
} elseif (!$reseau) {
    $point('avert', 'Exposition web non testée', 'php scripts/doctor.php --reseau, une fois le site en ligne.');
}

echo sprintf('%d réussi(s), %d avertissement(s), %d échec(s)', $compte['ok'], $compte['avert'], $compte['echec']) . PHP_EOL;
exit($compte['echec'] > 0 ? 1 : 0);
