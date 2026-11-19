<?php
declare(strict_types=1);

/*
 * Reprise des comptes du back-office de l'ancien site (décision du 2026-11-19) : table users (username, password_hash,
 * role, email) de l'ancienne base vers la table admins. Chaque personne garde son mot de passe (hachage password_hash,
 * relu par password_verify et re-haché en Argon2id à sa première connexion) et se connecte avec son adresse e-mail.
 * Rôles : admin => administrateur, employee => éditeur. Double authentification : rappel, puis obligatoire 30 jours
 * après la première connexion (reprise_2fa).
 *
 *   php scripts/reprendre-comptes.php <ancienne-base.sqlite | DSN PDO> [--simuler]
 *   DSN MySQL : identifiants dans l'environnement, jamais en argument (visibles dans la liste des processus) :
 *   ANCIENNE_BASE_UTILISATEUR=… ANCIENNE_BASE_MOT_DE_PASSE=… php scripts/reprendre-comptes.php 'mysql:host=…;dbname=…'
 *
 * L'ancienne base est ouverte en lecture seule. Aucun hachage n'est affiché. Ignorés, avec la raison : adresse
 * invalide, adresse en double dans l'ancienne base, compte déjà présent dans le nouveau site, hachage illisible.
 * Code de sortie : 0 si tout s'est passé sans erreur, 1 sinon.
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require __DIR__ . '/../app/bootstrap.php';

use App\Core\Db;

$args = array_values(array_filter(array_slice($argv, 1), static fn (string $a): bool => !str_starts_with($a, '--')));
$simuler = in_array('--simuler', $argv, true);
if (count($args) !== 1) {
    fwrite(STDERR, "Usage : php scripts/reprendre-comptes.php <ancienne-base.sqlite | DSN PDO> [--simuler]\n");
    exit(1);
}
$source = $args[0];

try {
    if (is_file($source)) {
        $ancienne = new PDO('sqlite:' . $source, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::SQLITE_ATTR_OPEN_FLAGS => PDO::SQLITE_OPEN_READONLY]);
    } else {
        $ancienne = new PDO($source, getenv('ANCIENNE_BASE_UTILISATEUR') ?: null, getenv('ANCIENNE_BASE_MOT_DE_PASSE') ?: null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    }
    $comptes = $ancienne->query('SELECT username, password_hash, role, email, created_at FROM users ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    fwrite(STDERR, "Ancienne base illisible : " . $e->getMessage() . "\n");
    exit(1);
}

// Hachages produits par password_hash (bcrypt, Argon2) : seuls lisibles par password_verify.
$lisible = static fn (string $h): bool => preg_match('/^\$(2[aby]|argon2i|argon2id)\$/', $h) === 1;
$vus = [];
$repris = 0;
$ignores = [];
foreach ($comptes as $c) {
    $nom = trim((string) $c['username']);
    $email = strtolower(trim((string) $c['email']));
    $hash = (string) $c['password_hash'];
    $role = ($c['role'] ?? '') === 'admin' ? 'admin' : 'editor';
    $qui = $nom !== '' ? $nom : '(sans nom)';
    if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
        $ignores[] = "$qui : adresse e-mail invalide ou absente";
        continue;
    }
    if (isset($vus[$email])) {
        $ignores[] = "$qui ($email) : adresse déjà prise par « {$vus[$email]} » dans l'ancienne base";
        continue;
    }
    $vus[$email] = $qui;
    if (!$lisible($hash)) {
        $ignores[] = "$qui ($email) : mot de passe dans un format illisible (à recréer par « mot de passe oublié »)";
        continue;
    }
    if (Db::run('SELECT 1 FROM admins WHERE email = ?', [$email])->fetchColumn()) {
        $ignores[] = "$qui ($email) : compte déjà présent dans le nouveau site";
        continue;
    }
    if (!$simuler) {
        Db::run(
            'INSERT INTO admins (email, name, password_hash, role, reprise_2fa, created_at) VALUES (?, ?, ?, ?, 1, ?)',
            [$email, $nom, $hash, $role, (string) ($c['created_at'] ?: now())],
        );
    }
    $repris++;
    fwrite(STDOUT, ($simuler ? '[simulation] ' : '') . "Repris : $qui ($email), " . ($role === 'admin' ? 'administrateur' : 'éditeur') . "\n");
}
foreach ($ignores as $raison) {
    fwrite(STDOUT, "Ignoré : $raison\n");
}
fwrite(STDOUT, sprintf("%s%d compte(s) repris, %d ignoré(s) sur %d. Connexion : adresse e-mail et mot de passe actuel ; double authentification rappelée puis obligatoire %d jours après la première connexion.\n",
    $simuler ? '[simulation] ' : '', $repris, count($ignores), count($comptes), App\Admin\AdminAuth::DELAI_REPRISE_JOURS));
exit(0);
