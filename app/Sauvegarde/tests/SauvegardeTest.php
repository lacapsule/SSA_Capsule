<?php
declare(strict_types=1);

/*
 * Tests des sauvegardes chiffrées, pour le lanceur de modules/tests-et-ci-php (php tests/run.php sauvegarde).
 * Tout se passe dans un dossier temporaire, avec une clé de test : jamais data/ ni la BACKUP_KEY du .env.
 */

use App\Sauvegarde\Sauvegarde;

/** @return array{0: Sauvegarde, 1: string, 2: string} instance, dossier de travail, chemin de la base */
$preparer = static function (): array {
    $dossier = sys_get_temp_dir() . '/test-sauvegarde-' . bin2hex(random_bytes(4));
    mkdir($dossier, 0700, true);
    $base = $dossier . '/base.sqlite';
    $pdo = new PDO('sqlite:' . $base);
    $pdo->exec('CREATE TABLE admins (id INTEGER PRIMARY KEY, email TEXT NOT NULL)');
    $pdo->exec("INSERT INTO admins (email) VALUES ('compte1@exemple.fr'), ('compte2@exemple.fr')");
    $s = new Sauvegarde($base, $dossier . '/sauvegardes', sodium_crypto_secretstream_xchacha20poly1305_keygen(), [
        'tables_requises' => ['admins'], 'tables_comptees' => ['admins'],
    ]);
    return [$s, $dossier, $base];
};

return [
    'une sauvegarde modifiée d\'un octet est refusée' => function () use ($preparer): void {
        [$s, $dossier] = $preparer();
        try {
            $r = $s->creer('quotidienne');
            affirmer($r['chiffre'], 'sauvegarde non chiffrée');
            affirmer_egal(0600, fileperms($r['fichier']) & 0777, 'droits du fichier');
            affirmer($s->verifier($r['fichier'])['ok'], 'sauvegarde intacte refusée');
            $octets = (string) file_get_contents($r['fichier']);
            foreach ([10, intdiv(strlen($octets), 2), strlen($octets) - 1] as $position) {
                $altere = $octets;
                $altere[$position] = chr(ord($altere[$position]) ^ 1);
                file_put_contents($r['fichier'], $altere);
                affirmer(!$s->verifier($r['fichier'])['ok'], "octet $position modifié mais accepté");
            }
            file_put_contents($r['fichier'], substr($octets, 0, -40));
            affirmer(!$s->verifier($r['fichier'])['ok'], 'fichier tronqué accepté');
        } finally {
            Sauvegarde::supprimerDossier($dossier);
        }
    },

    'la restauration fait d\'abord une sauvegarde de sécurité' => function () use ($preparer): void {
        [$s, $dossier, $base] = $preparer();
        try {
            $r = $s->creer('quotidienne');
            (new PDO('sqlite:' . $base))->exec("INSERT INTO admins (email) VALUES ('apres@exemple.fr')");
            $res = $s->restaurer($r['fichier']);
            affirmer($res['securite'] !== null && is_file($res['securite']), 'sauvegarde de sécurité absente');
            affirmer_egal(2, (int) (new PDO('sqlite:' . $base))->query('SELECT COUNT(*) FROM admins')->fetchColumn(), 'lignes après restauration');
            affirmer_egal(3, $s->verifier($res['securite'])['comptes']['admins']['sauvegarde'], 'la sauvegarde de sécurité garde l\'état d\'avant');
        } finally {
            Sauvegarde::supprimerDossier($dossier);
        }
    },
];
