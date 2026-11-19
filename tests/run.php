<?php
declare(strict_types=1);

/*
 * Lanceur de tests sans PHPUnit ni Composer.
 *   php tests/run.php            tous les tests
 *   php tests/run.php sauvegarde  seulement ceux dont le fichier ou le nom contient « sauvegarde »
 *
 * Un fichier de tests (tests/<dossier>/<Nom>Test.php ou app/<Domaine>/tests/<Nom>Test.php) renvoie un tableau
 * ['ce que le test vérifie' => function (PDO $db): void { ... }]. Chaque test reçoit une base SQLite NEUVE :
 * copie d'une base modèle où les migrations du projet ont été appliquées une fois (rapide). Avec le socle PHP,
 * App\Core\Db pointe aussi sur cette base (DB_DSN) et sa connexion est refermée entre deux tests.
 * Assertions : tests/assertions.php. Code de sortie : 0 si tout passe, 1 sinon (CI, script de paquet).
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$racine = dirname(__DIR__);
$filtre = (string) ($argv[1] ?? '');
$couleur = getenv('NO_COLOR') === false && function_exists('stream_isatty') && stream_isatty(STDOUT);
$teinte = static fn (string $texte, string $code): string => $couleur ? "\033[{$code}m$texte\033[0m" : $texte;

// Dossier de travail privé, effacé à la fin même en cas d'erreur fatale.
$travail = sys_get_temp_dir() . '/tests-' . getmypid() . '-' . bin2hex(random_bytes(3));
mkdir($travail, 0700, true);
register_shutdown_function(static function () use ($travail): void {
    foreach (glob($travail . '/*') ?: [] as $f) {
        @unlink($f);
    }
    @rmdir($travail);
});

// Environnement de test, posé AVANT le démarrage du socle (Env : l'environnement du processus prime sur le .env).
$modele = $travail . '/modele.sqlite';
$env = ['APP_ENV' => 'test', 'APP_KEY' => str_repeat('ab', 32), 'BACKUP_KEY' => str_repeat('cd', 32), 'DB_DSN' => 'sqlite:' . $modele, 'DB_USER' => '', 'DB_PASSWORD' => ''];
foreach ($env as $k => $v) {
    putenv("$k=$v");
    $_ENV[$k] = $v;
}

$socle = is_file($racine . '/app/bootstrap.php');
if ($socle) {
    require $racine . '/app/bootstrap.php';
}
require __DIR__ . '/assertions.php';

/** Referme la connexion partagée du socle (App\Core\Db garde son PDO en propriété statique privée). */
$fermerDb = static function (): void {
    if (class_exists(\App\Core\Db::class, false)) {
        (new ReflectionProperty(\App\Core\Db::class, 'pdo'))->setValue(null, null);
    }
};
$pointerDb = static function (string $fichier) use ($fermerDb): void {
    putenv('DB_DSN=sqlite:' . $fichier);
    $_ENV['DB_DSN'] = 'sqlite:' . $fichier;
    $fermerDb();
};

// Base modèle : migrations appliquées une seule fois.
try {
    if ($socle && is_dir($racine . '/migrations') && class_exists(\App\Core\Migrator::class)) {
        $pointerDb($modele);
        \App\Core\Migrator::run();
        $fermerDb();
    } else {
        (new PDO('sqlite:' . $modele))->exec('PRAGMA user_version = 1');
    }
} catch (Throwable $e) {
    fwrite(STDERR, $teinte('ÉCHEC', '31') . ' migrations de la base modèle : ' . $e->getMessage() . PHP_EOL);
    exit(1);
}

$fichiers = array_merge(glob(__DIR__ . '/*/*Test.php') ?: [], glob($racine . '/app/*/tests/*Test.php') ?: []);
sort($fichiers, SORT_STRING);

$reussis = 0;
$echecs = [];
$debut = hrtime(true);
foreach ($fichiers as $fichier) {
    $nomFichier = substr($fichier, strlen($racine) + 1);
    $tests = require $fichier;
    if (!is_array($tests)) {
        $echecs[] = [$nomFichier, '(fichier)', 'Le fichier doit renvoyer un tableau [nom => fonction].', ''];
        continue;
    }
    $titre = false;
    foreach ($tests as $nom => $test) {
        if ($filtre !== '' && stripos($nomFichier . ' ' . $nom, $filtre) === false) {
            continue;
        }
        if (!$titre) {
            echo PHP_EOL . $teinte($nomFichier, '1') . PHP_EOL;
            $titre = true;
        }
        $base = $travail . '/test.sqlite';
        foreach ([$base, $base . '-wal', $base . '-shm'] as $f) {
            @unlink($f);
        }
        copy($modele, $base);
        $pointerDb($base);
        $_SESSION = $_POST = $_GET = $_COOKIE = $_FILES = [];
        $db = new PDO('sqlite:' . $base, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
        $t = hrtime(true);
        $tampon = ob_get_level();
        ob_start(); // un test qui affiche (gabarit, echo) ne brouille pas le rapport
        try {
            $test($db);
            $ms = (int) ((hrtime(true) - $t) / 1e6);
            ob_end_clean();
            $reussis++;
            echo '  ' . $teinte('ok   ', '32') . " $nom " . $teinte("($ms ms)", '2') . PHP_EOL;
        } catch (Throwable $e) {
            while (ob_get_level() > $tampon) {
                ob_end_clean();
            }
            $lieu = basename($e->getFile()) . ':' . $e->getLine();
            foreach ($e->getTrace() as $etape) {
                if (($etape['file'] ?? '') === $fichier) {
                    $lieu = basename($fichier) . ':' . $etape['line'];
                    break;
                }
            }
            $echecs[] = [$nomFichier, (string) $nom, $e->getMessage(), $lieu];
            echo '  ' . $teinte('ÉCHEC', '31') . " $nom" . PHP_EOL;
        }
        $db = null;
        $fermerDb();
    }
}

$duree = number_format((hrtime(true) - $debut) / 1e9, 2, ',', '');
echo PHP_EOL . ($echecs === [] ? $teinte("$reussis réussi(s), 0 échec", '32') : $teinte("$reussis réussi(s), " . count($echecs) . ' échec(s)', '31')) . " en $duree s" . PHP_EOL;
foreach ($echecs as [$f, $nom, $message, $lieu]) {
    echo PHP_EOL . '  ' . $teinte('ÉCHEC', '31') . " $f : $nom" . PHP_EOL . "        $message" . PHP_EOL . ($lieu !== '' ? "        $lieu" . PHP_EOL : '');
}
if ($reussis === 0 && $echecs === []) {
    echo 'Aucun test trouvé' . ($filtre !== '' ? " pour « $filtre »" : '') . '.' . PHP_EOL;
}
exit($echecs === [] ? 0 : 1);
