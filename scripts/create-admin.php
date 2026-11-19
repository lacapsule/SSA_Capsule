<?php
declare(strict_types=1);

/*
 * Crée un compte administrateur (mot de passe haché en Argon2id, jamais affiché).
 *   php scripts/create-admin.php <e-mail> [--name="Prénom Nom"]
 * Mot de passe (12 caractères au moins) lu sur l'entrée standard, une ligne :
 *   printf '%s\n' "$MOT_DE_PASSE" | php scripts/create-admin.php admin@exemple.org
 * Saisi au clavier, il n'est pas affiché quand le terminal le permet. Aucune option --password : elle resterait visible
 * dans la liste des processus et l'historique du terminal.
 * À la première connexion, l'administrateur enrôle sa double authentification (QR code) : aucun secret n'est créé ici.
 * Refuse un e-mail déjà présent. Code de sortie : 0 si le compte est créé, 1 sinon.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require dirname(__DIR__) . '/app/bootstrap.php';
require __DIR__ . '/read-password.php';

use App\Admin\AdminAuth;
use App\Core\Db;

$fail = static function (string $message): never {
    fwrite(STDERR, "Échec : $message" . PHP_EOL);
    exit(1);
};

$email = '';
$name = '';
foreach (array_slice($argv, 1) as $arg) {
    if (str_starts_with($arg, '--name=')) {
        $name = trim(substr($arg, 7));
    } elseif (str_starts_with($arg, '--')) {
        $fail("option inconnue : $arg (seule --name existe ; le mot de passe se donne sur l'entrée standard).");
    } else {
        $email = strtolower(trim($arg));
    }
}
if ($email === '') {
    fwrite(STDERR, 'Usage : php scripts/create-admin.php <e-mail> [--name="Nom"] (mot de passe sur l\'entrée standard)' . PHP_EOL);
    exit(1);
}
if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
    $fail("adresse e-mail invalide : $email");
}
if (!defined('PASSWORD_ARGON2ID')) {
    $fail('ce PHP ne propose pas Argon2id (extension sodium ou libargon2 requise). Aucun compte créé.');
}

$password = read_password('Mot de passe (' . AdminAuth::MIN_PASSWORD . ' caractères au moins) : ');
if (text_length($password) < AdminAuth::MIN_PASSWORD) {
    $fail('mot de passe trop court (' . AdminAuth::MIN_PASSWORD . ' caractères au moins). Aucun compte créé.');
}

try {
    if (Db::run('SELECT 1 FROM admins WHERE email = ?', [$email])->fetchColumn()) {
        $fail("un administrateur existe déjà avec l'adresse $email. Aucun compte créé.");
    }
    $name = preg_match('/^.{0,120}/su', $name, $m) === 1 ? $m[0] : '';
    Db::run('INSERT INTO admins (email, name, password_hash, created_at) VALUES (?, ?, ?, ?)',
        [$email, $name, password_hash($password, PASSWORD_ARGON2ID), now()]);
} catch (Throwable $e) {
    $fail($e->getMessage() . ' (les migrations sont-elles appliquées ? php scripts/migrate.php)');
}

fwrite(STDOUT, "Administrateur créé : $email. À la première connexion, il configure sa double authentification." . PHP_EOL);
exit(0);
