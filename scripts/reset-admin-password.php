<?php
declare(strict_types=1);

/*
 * Secours depuis le serveur : nouveau mot de passe pour un administrateur qui a perdu le sien.
 *   printf '%s\n' "$NOUVEAU_MOT_DE_PASSE" | php scripts/reset-admin-password.php admin@exemple.org
 *   printf '%s\n' "$NOUVEAU_MOT_DE_PASSE" | php scripts/reset-admin-password.php admin@exemple.org --reset-2fa
 * Mot de passe lu sur l'entrée standard (12 caractères au moins), haché en Argon2id, jamais affiché.
 * Toutes les sessions ouvertes du compte sont fermées et le blocage après échecs est levé.
 * Sans option, la double authentification est gardée (même application, mêmes codes de secours).
 * --reset-2fa : téléphone perdu ; secret et codes de secours effacés, nouvel enrôlement à la prochaine connexion.
 * Code de sortie : 0 si le mot de passe est changé, 1 sinon (rien n'est modifié).
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
$resetTwoFactor = false;
foreach (array_slice($argv, 1) as $arg) {
    if ($arg === '--reset-2fa') {
        $resetTwoFactor = true;
    } elseif (str_starts_with($arg, '--')) {
        $fail("option inconnue : $arg (seule --reset-2fa existe ; le mot de passe se donne sur l'entrée standard).");
    } else {
        $email = strtolower(trim($arg));
    }
}
if ($email === '') {
    fwrite(STDERR, 'Usage : php scripts/reset-admin-password.php <e-mail> [--reset-2fa] (nouveau mot de passe sur l\'entrée standard)' . PHP_EOL);
    exit(1);
}
if (!defined('PASSWORD_ARGON2ID')) {
    $fail('ce PHP ne propose pas Argon2id (extension sodium ou libargon2 requise). Rien n\'a été modifié.');
}

try {
    $adminId = (int) Db::run('SELECT id FROM admins WHERE email = ?', [$email])->fetchColumn();
} catch (Throwable $e) {
    $fail($e->getMessage() . ' (les migrations sont-elles appliquées ? php scripts/migrate.php)');
}
if ($adminId === 0) {
    $fail("aucun administrateur avec l'adresse $email. Rien n'a été modifié.");
}

$password = read_password('Nouveau mot de passe (' . AdminAuth::MIN_PASSWORD . ' caractères au moins) : ');
if (text_length($password) < AdminAuth::MIN_PASSWORD) {
    $fail('mot de passe trop court (' . AdminAuth::MIN_PASSWORD . ' caractères au moins). Rien n\'a été modifié.');
}

try {
    Db::transaction(static fn () => AdminAuth::resetPassword($adminId, $password, $resetTwoFactor));
} catch (Throwable $e) {
    $fail($e->getMessage() . ' (rien n\'a été modifié)');
}

fwrite(STDOUT, "Mot de passe changé pour $email. Sessions ouvertes fermées." . PHP_EOL);
fwrite(STDOUT, $resetTwoFactor
    ? 'Double authentification réinitialisée : à la prochaine connexion, nouvel enrôlement par QR code et nouveaux codes de secours.' . PHP_EOL
    : 'Double authentification gardée : la connexion demande toujours le code de l\'application (ou un code de secours).' . PHP_EOL);
exit(0);
