<?php
declare(strict_types=1);

/*
 * Sauvegardes chiffrées de la base SQLite, en ligne de commande et en tâche planifiée.
 *
 *   php scripts/sauvegarde.php creer [--type=quotidienne]   sauvegarde chiffrée, puis rétention (30 jours, 12 mois)
 *   php scripts/sauvegarde.php verifier [nom]               vérifie la plus récente (ou celle nommée) ; code 1 si inutilisable
 *   php scripts/sauvegarde.php restaurer <nom> [--oui]      vérifie, demande de taper RESTAURER, sauvegarde de sécurité, remplace
 *   php scripts/sauvegarde.php liste                        sauvegardes présentes
 *   php scripts/sauvegarde.php copier                       copie la plus récente hors du serveur (BACKUP_REMOTE)
 *   php scripts/sauvegarde.php etat                         code 1 si la dernière sauvegarde est trop ancienne ou la vérification en échec
 *   php scripts/sauvegarde.php cle [--ecrire]               génère BACKUP_KEY (l'écrit dans .env si --ecrire et si elle est vide)
 *
 * Code de sortie 0 si tout va bien : la tâche planifiée de l'hébergeur peut prévenir par e-mail en cas d'échec.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require dirname(__DIR__) . '/app/bootstrap.php';

use App\Sauvegarde\Sauvegarde;

$ecrire = static function (string $ligne = ''): void {
    fwrite(STDOUT, $ligne . PHP_EOL);
};
$niveau = static fn (string $n): string => ['ok' => '[ OK  ]', 'avert' => '[AVERT]', 'echec' => '[ÉCHEC]'][$n] ?? '[  ?  ]';

$args = array_slice($argv, 1);
$commande = (string) (array_shift($args) ?? 'aide');
$options = [];
$positionnels = [];
foreach ($args as $a) {
    if (preg_match('/^--([a-z-]+)(?:=(.*))?$/', $a, $m) === 1) {
        $options[$m[1]] = $m[2] ?? '1';
    } else {
        $positionnels[] = $a;
    }
}

$rappel = 'Rappel : BACKUP_KEY n\'est pas dans la sauvegarde. Gardez-en une copie hors de ce serveur (gestionnaire de mots de passe), '
    . 'jamais à côté des sauvegardes : sans elle, elles sont illisibles.';

$afficherVerification = static function (array $r) use ($ecrire, $niveau): void {
    $ecrire('Fichier : ' . basename((string) $r['fichier']) . ($r['cree_le'] !== '' ? ' (créé le ' . date('d/m/Y à H:i', (int) strtotime((string) $r['cree_le'])) . ')' : ''));
    if ($r['schema'] !== '') {
        $ecrire('Dernière migration dans la sauvegarde : ' . $r['schema']);
    }
    if ($r['comptes'] !== []) {
        $ecrire(sprintf('  %-22s %12s %12s', 'Table', 'Sauvegarde', 'Actuelle'));
        foreach ($r['comptes'] as $t => $c) {
            $ecrire(sprintf('  %-22s %12s %12s', $t, $c['sauvegarde'] ?? 'absente', $c['actuelle'] ?? 'absente'));
        }
    }
    foreach ($r['problemes'] as $p) {
        $ecrire($niveau('echec') . ' ' . $p);
    }
    $ecrire($r['ok']
        ? $niveau('ok') . ' Sauvegarde valide : déchiffrement, décompression et intégrité SQLite vérifiés.'
        : $niveau('echec') . ' Sauvegarde inutilisable.');
};

try {
    if ($commande === 'cle') {
        $cle = Sauvegarde::genererCle();
        if (!isset($options['ecrire'])) {
            $ecrire('BACKUP_KEY=' . $cle);
            $ecrire('À copier dans le .env, et dans un gestionnaire de mots de passe hors du serveur.');
            exit(0);
        }
        $env = ROOT . '/.env';
        $contenu = is_file($env) ? (string) file_get_contents($env) : '';
        if (preg_match('/^BACKUP_KEY=\S+/m', $contenu) === 1) {
            $ecrire($niveau('echec') . ' BACKUP_KEY existe déjà : la changer rendrait les sauvegardes existantes illisibles. Rien n\'a été modifié.');
            exit(1);
        }
        $contenu = preg_match('/^BACKUP_KEY=.*$/m', $contenu) === 1
            ? (string) preg_replace('/^BACKUP_KEY=.*$/m', 'BACKUP_KEY=' . $cle, $contenu)
            : rtrim($contenu) . "\nBACKUP_KEY=$cle\n";
        file_put_contents($env . '.tmp', $contenu, LOCK_EX);
        chmod($env . '.tmp', 0600);
        rename($env . '.tmp', $env);
        $ecrire($niveau('ok') . ' BACKUP_KEY écrite dans .env (droits 0600).');
        $ecrire('  Copiez-la maintenant hors du serveur : sans elle, les sauvegardes sont illisibles.');
        exit(0);
    }

    $s = Sauvegarde::depuisConfig();
    $nomOuChemin = static function (?string $arg) use ($s): ?string {
        if ($arg === null) {
            return $s->plusRecente()['fichier'] ?? null;
        }
        return str_contains($arg, '/') ? $arg : $s->chemin($arg);
    };

    switch ($commande) {
        case 'creer':
            $r = $s->creer((string) ($options['type'] ?? 'manuelle'));
            if (!$r['chiffre']) {
                $ecrire($niveau('avert') . ' BACKUP_KEY absente : sauvegarde NON chiffrée (acceptée seulement en local).');
            }
            $ecrire($niveau('ok') . ' Sauvegarde ' . ($r['chiffre'] ? 'chiffrée' : 'non chiffrée') . ' : ' . $r['nom'] . ' (' . Sauvegarde::tailleLisible($r['taille']) . ', droits 0600)');
            if ($r['supprimes'] !== []) {
                $ecrire('  Rétention : ' . count($r['supprimes']) . ' ancienne(s) sauvegarde(s) supprimée(s).');
            }
            if ((string) ($_ENV['BACKUP_REMOTE'] ?? '') === '') {
                $ecrire('  Copie hors du serveur non configurée (BACKUP_REMOTE) : voir « php scripts/sauvegarde.php copier ».');
            }
            $ecrire($rappel);
            exit(0);

        case 'verifier':
            $f = $nomOuChemin($positionnels[0] ?? null);
            if ($f === null) {
                $ecrire($niveau('echec') . ' Aucune sauvegarde à vérifier.');
                $s->memoriserVerification(['fichier' => '(aucune)', 'ok' => false, 'problemes' => ['Aucune sauvegarde à vérifier.']]);
                exit(1);
            }
            $r = $s->verifier($f);
            $afficherVerification($r);
            $s->memoriserVerification($r);
            exit($r['ok'] ? 0 : 1);

        case 'restaurer':
            if (!isset($positionnels[0])) {
                $ecrire('Usage : php scripts/sauvegarde.php restaurer <nom>   (noms : php scripts/sauvegarde.php liste)');
                exit(1);
            }
            $f = (string) $nomOuChemin($positionnels[0]);
            $ecrire('1. Vérification de la sauvegarde...');
            $r = $s->verifier($f);
            $afficherVerification($r);
            if (!$r['ok']) {
                $ecrire('Restauration annulée : rien n\'a été modifié.');
                exit(1);
            }
            if (!isset($options['oui'])) {
                $ecrire();
                $ecrire('ATTENTION : la base actuelle va être remplacée par cette sauvegarde. Ce qui a été saisi depuis sera perdu');
                $ecrire('(une sauvegarde de sécurité de la base actuelle est faite juste avant).');
                fwrite(STDOUT, 'Tapez RESTAURER pour confirmer : ');
                if (trim((string) fgets(STDIN)) !== 'RESTAURER') {
                    $ecrire('Restauration annulée : rien n\'a été modifié.');
                    exit(1);
                }
            }
            $ecrire('2. Sauvegarde de sécurité de la base actuelle, puis remplacement...');
            $res = $s->restaurer($f);
            $ecrire('   Sauvegarde de sécurité : ' . ($res['securite'] !== null ? basename($res['securite']) : 'aucune (pas de base actuelle)'));
            $ecrire('3. Base remplacée (' . $s->base() . ', méthode ' . $res['methode'] . ').');
            $ecrire('4. Si la sauvegarde est plus ancienne que le code : php scripts/migrate.php');
            $ecrire($niveau('ok') . ' Restauration terminée.');
            exit(0);

        case 'liste':
            $liste = $s->lister();
            if ($liste === []) {
                $ecrire('Aucune sauvegarde dans ' . $s->dossier());
                exit(0);
            }
            foreach ($liste as $l) {
                $ecrire(sprintf('  %s  %-20s %10s  %s  %s', $l['date'], $l['type'], Sauvegarde::tailleLisible($l['taille']), $l['chiffre'] ? 'chiffrée    ' : 'NON chiffrée', $l['nom']));
            }
            exit(0);

        case 'copier':
            $f = $s->plusRecente()['fichier'] ?? null;
            if ($f === null) {
                $ecrire($niveau('echec') . ' Aucune sauvegarde à copier : php scripts/sauvegarde.php creer');
                exit(1);
            }
            $distante = (string) ($_ENV['BACKUP_REMOTE'] ?? '');
            if ($distante === '') {
                $ecrire('Copie hors du serveur non configurée. Ajoutez dans .env une commande où {fichier} est le chemin de la sauvegarde :');
                $ecrire('  BACKUP_REMOTE=rclone copy {fichier} stockage-distant:sauvegardes');
                $ecrire('  BACKUP_REMOTE=scp -q {fichier} sauvegarde@serveur-de-secours.exemple:sauvegardes/');
                $ecrire('La copie est chiffrée : illisible sans BACKUP_KEY, à ne jamais copier au même endroit.');
                exit(0);
            }
            $r = Sauvegarde::copierHorsServeur($distante, $f);
            foreach ($r['sortie'] as $l) {
                $ecrire('  ' . $l);
            }
            if ($r['code'] !== 0) {
                $ecrire($niveau('echec') . ' La copie a échoué (code ' . $r['code'] . ').');
                error_log('Sauvegarde : copie hors serveur en échec (code ' . $r['code'] . ', ' . basename($f) . ')');
                exit(1);
            }
            $ecrire($niveau('ok') . ' Copiée hors du serveur : ' . basename($f));
            exit(0);

        case 'etat':
            $code = 0;
            $derniere = $s->plusRecente();
            $max = (int) config('sauvegarde.alerte_sauvegarde_jours', 2);
            if ($derniere === null || strtotime($derniere['date']) < time() - $max * 86400) {
                $ecrire($niveau('echec') . ' ' . ($derniere === null ? 'Aucune sauvegarde.' : 'Dernière sauvegarde du ' . $derniere['date'] . " (plus de $max jours)."));
                $code = 1;
            } else {
                $ecrire($niveau('ok') . ' Dernière sauvegarde : ' . $derniere['date'] . ' (' . $derniere['nom'] . ')');
            }
            $v = $s->derniereVerification();
            $maxV = (int) config('sauvegarde.alerte_verification_jours', 8);
            if ($v === null) {
                $ecrire($niveau('avert') . ' Aucune vérification enregistrée : php scripts/sauvegarde.php verifier');
            } elseif (!$v['ok']) {
                $ecrire($niveau('echec') . ' Dernière vérification en échec (' . $v['fichier'] . ') : ' . implode(' ; ', $v['problemes']));
                $code = 1;
            } elseif (strtotime($v['date']) < time() - $maxV * 86400) {
                $ecrire($niveau('avert') . ' Dernière vérification réussie, mais du ' . $v['date'] . '.');
            } else {
                $ecrire($niveau('ok') . ' Dernière vérification réussie le ' . $v['date'] . '.');
            }
            if (!$s->estChiffree()) {
                $ecrire($niveau(is_local() ? 'avert' : 'echec') . ' BACKUP_KEY absente du .env.');
                $code = is_local() ? $code : 1;
            }
            exit($code);

        default:
            $ecrire('Commandes : creer [--type=], verifier [nom], restaurer <nom> [--oui], liste, copier, etat, cle [--ecrire]');
            exit($commande === 'aide' ? 0 : 1);
    }
} catch (Throwable $e) {
    fwrite(STDERR, $niveau('echec') . ' ' . $e->getMessage() . PHP_EOL);
    exit(1);
}
