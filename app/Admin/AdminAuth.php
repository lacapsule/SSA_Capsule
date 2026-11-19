<?php
declare(strict_types=1);

namespace App\Admin;

use App\Core\Crypto;
use App\Core\Csrf;
use App\Core\Db;
use App\Core\FormGuard;
use App\Core\Request;
use App\Core\Session;

/**
 * Connexion au back-office en deux étapes : mot de passe, puis code TOTP (ou code de secours à usage unique).
 * Étape intermédiaire limitée (5 minutes, 5 essais), blocage du compte après 5 échecs, limite par IP.
 * Rien ne révèle qu'un compte existe : même message et même travail pour un e-mail inconnu, un mauvais mot de passe
 * ou un compte bloqué. Un changement de mot de passe ferme toutes les autres sessions (empreinte HMAC en session).
 */
final class AdminAuth
{
    private const PENDING_TTL = 300;
    private const PENDING_ATTEMPTS = 5;
    private const LOCK_AFTER = 5;
    private const LOCK_MINUTES = 15;
    private const IDLE_SECONDS = 7200;
    private const ABSOLUTE_SECONDS = 43200;
    private const RECOVERY_ALPHABET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    /** Hachage d'une valeur aléatoire inconnue : un e-mail inconnu coûte autant de calcul qu'un vrai compte. */
    private const DUMMY_HASH = '$argon2id$v=19$m=65536,t=4,p=1$b1h1enAyY0daU01UWWJMQg$jVgfGl4ejcAoAmxZtCGIyzIektz9La5g5DXxU6jy8Tw';

    public const RECOVERY_COUNT = 10;
    public const MIN_PASSWORD = 12;
    public const RESET_MINUTES = 30;
    private const RESET_PER_ACCOUNT = 3;

    /** Seul message d'échec de l'étape 1, quel que soit le motif. */
    public const LOGIN_FAILED = 'Connexion refusée : e-mail ou mot de passe incorrect. Après plusieurs essais, la connexion est bloquée quelques minutes.';

    /** @return array<string, mixed>|null administrateur entièrement connecté (mot de passe et second facteur) */
    public static function current(): ?array
    {
        Session::resume();
        $id = $_SESSION['admin_id'] ?? null;
        if (!is_int($id)) {
            return null;
        }
        $idle = time() - (int) ($_SESSION['admin_seen'] ?? 0) > self::IDLE_SECONDS;
        $old = time() - (int) ($_SESSION['admin_since'] ?? 0) > self::ABSOLUTE_SECONDS;
        if ($idle || $old) {
            self::forget();
            Session::flash($idle ? 'Session fermée après 2 heures d\'inactivité. Reconnectez-vous.' : 'Session fermée après 12 heures. Reconnectez-vous.', 'info');
            return null;
        }
        // SELECT * : les colonnes ajoutées par d'autres modules (role, perimetre de roles-et-journal) suivent ; les secrets sont retirés plus bas.
        $admin = Db::run('SELECT * FROM admins WHERE id = ?', [$id])->fetch();
        if (!$admin || !hash_equals(self::fingerprint((string) $admin['password_hash']), (string) ($_SESSION['admin_pw'] ?? ''))) {
            self::forget();
            Session::flash('Le mot de passe de ce compte a changé : reconnectez-vous.', 'info');
            return null;
        }
        $_SESSION['admin_seen'] = time();
        unset($admin['password_hash'], $admin['totp_secret'], $admin['totp_last_step'], $admin['failed_logins'], $admin['locked_until']);
        return $admin;
    }

    /** @return array<string, mixed> */
    public static function require(): array
    {
        $admin = self::current();
        if ($admin === null) {
            // Page demandée gardée pour y revenir après la connexion (GET seulement : un envoi de formulaire ne se rejoue pas).
            $page = ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET' ? AdminController::safeReturn($_SERVER['REQUEST_URI'] ?? null) : null;
            redirect(AdminController::url('connexion/') . ($page !== null ? '?retour=' . rawurlencode($page) : ''));
        }
        return $admin;
    }

    /**
     * Étape 1. Renvoie 'verify' (code à saisir), 'enroll' (double authentification à configurer) ou un message d'erreur.
     */
    public static function password(string $email, string $password): string
    {
        if (FormGuard::rateLimited('admin-login', 8, 15)) {
            return 'Trop de tentatives depuis cette connexion. Réessayez dans un quart d\'heure.';
        }
        $email = strtolower(trim($email));
        $admin = $email !== '' ? Db::run('SELECT * FROM admins WHERE email = ?', [$email])->fetch() : false;
        // Vérification toujours faite (hachage factice si le compte n'existe pas) : même durée dans tous les cas.
        $valid = password_verify($password, $admin ? (string) $admin['password_hash'] : self::DUMMY_HASH);
        $locked = $admin && $admin['locked_until'] !== null && $admin['locked_until'] > now();
        if (!$admin || !$valid || $locked) {
            self::failure($admin ? (int) $admin['id'] : 0);
            return self::LOGIN_FAILED;
        }
        if (password_needs_rehash((string) $admin['password_hash'], self::algo())) {
            $admin['password_hash'] = self::setPassword((int) $admin['id'], $password);
        }
        Session::start();
        Session::regenerate();
        $_SESSION['admin_pending'] = ['id' => (int) $admin['id'], 'at' => time(), 'tries' => 0, 'pw' => self::fingerprint((string) $admin['password_hash'])];
        if ($admin['totp_enabled_at'] === null) {
            $_SESSION['admin_enroll_secret'] ??= Totp::secret();
            return 'enroll';
        }
        return 'verify';
    }

    /** @return array<string, mixed>|null administrateur en attente du second facteur */
    public static function pending(): ?array
    {
        Session::resume();
        $p = $_SESSION['admin_pending'] ?? null;
        if (!is_array($p) || time() - (int) $p['at'] > self::PENDING_TTL || (int) $p['tries'] >= self::PENDING_ATTEMPTS) {
            unset($_SESSION['admin_pending'], $_SESSION['admin_enroll_secret']);
            return null;
        }
        $admin = Db::run('SELECT * FROM admins WHERE id = ?', [$p['id']])->fetch();
        $locked = $admin && $admin['locked_until'] !== null && $admin['locked_until'] > now();
        if (!$admin || $locked || !hash_equals(self::fingerprint((string) $admin['password_hash']), (string) ($p['pw'] ?? ''))) {
            unset($_SESSION['admin_pending'], $_SESSION['admin_enroll_secret']);
            return null;
        }
        return $admin;
    }

    /** Étape 2 : code de l'application ou code de secours. */
    public static function verify(string $code): bool
    {
        $admin = self::pending();
        if ($admin === null) {
            return false;
        }
        if (!self::consumeTotp($admin, $code) && !self::consumeRecovery((int) $admin['id'], $code)) {
            $_SESSION['admin_pending']['tries']++;
            self::failure((int) $admin['id']);
            return false;
        }
        self::complete((int) $admin['id']);
        return true;
    }

    /** Enrôlement : le code saisi prouve que l'application a bien enregistré le secret. @return list<string>|null codes de secours */
    public static function enroll(string $code): ?array
    {
        $admin = self::pending();
        $secret = $_SESSION['admin_enroll_secret'] ?? null;
        if ($admin === null || !is_string($secret)) {
            return null;
        }
        $step = Totp::matchStep($secret, $code);
        if ($step === null) {
            $_SESSION['admin_pending']['tries']++;
            return null;
        }
        $codes = Db::transaction(static function () use ($admin, $secret, $step): array {
            Db::run('UPDATE admins SET totp_secret = ?, totp_enabled_at = ?, totp_last_step = ? WHERE id = ?', [Crypto::seal($secret), now(), $step, $admin['id']]);
            return self::regenerateRecovery((int) $admin['id']);
        });
        unset($_SESSION['admin_enroll_secret']);
        self::complete((int) $admin['id'], '2fa.activee');
        return $codes;
    }

    /** Secret en cours d'enrôlement (QR code), pour l'écran de configuration. */
    public static function enrollSecret(): ?string
    {
        $s = $_SESSION['admin_enroll_secret'] ?? null;
        return is_string($s) ? $s : null;
    }

    /** Nouveaux codes de secours : les anciens ne valent plus rien. @return list<string> */
    public static function regenerateRecovery(int $adminId): array
    {
        $codes = [];
        for ($i = 0; $i < self::RECOVERY_COUNT; $i++) {
            $raw = '';
            for ($j = 0; $j < 10; $j++) {
                $raw .= self::RECOVERY_ALPHABET[random_int(0, strlen(self::RECOVERY_ALPHABET) - 1)];
            }
            $codes[] = substr($raw, 0, 5) . '-' . substr($raw, 5);
        }
        Db::run('DELETE FROM admin_recovery_codes WHERE admin_id = ?', [$adminId]);
        foreach ($codes as $c) {
            Db::run('INSERT INTO admin_recovery_codes (admin_id, code_hash) VALUES (?, ?)', [$adminId, Crypto::mac(self::normalize($c))]);
        }
        return $codes;
    }

    public static function recoveryLeft(int $adminId): int
    {
        return (int) Db::run('SELECT COUNT(*) FROM admin_recovery_codes WHERE admin_id = ? AND used_at IS NULL', [$adminId])->fetchColumn();
    }

    public static function logout(): void
    {
        self::log('deconnexion');
        self::forget();
        Session::regenerate();
    }

    /** Nouveau téléphone : enrôlement repris, le compte reste protégé par la session et un nouveau code. */
    public static function startReenroll(): void
    {
        $admin = self::require();
        $_SESSION['admin_pending'] = ['id' => (int) $admin['id'], 'at' => time(), 'tries' => 0, 'pw' => (string) ($_SESSION['admin_pw'] ?? '')];
        $_SESSION['admin_enroll_secret'] = Totp::secret();
        unset($_SESSION['admin_id']);
    }

    /**
     * Changement depuis le back-office : les autres sessions de ce compte sont fermées, celle-ci reste ouverte.
     * @return array<string, string> erreurs par champ (current, new, confirm), vide si le mot de passe est changé
     */
    public static function changePassword(int $adminId, string $current, string $new, string $confirm): array
    {
        if (FormGuard::rateLimited('admin-password', 5, 15)) {
            return ['current' => 'Trop d\'essais. Réessayez dans un quart d\'heure.'];
        }
        $hash = (string) Db::run('SELECT password_hash FROM admins WHERE id = ?', [$adminId])->fetchColumn();
        $errors = [];
        if (!password_verify($current, $hash)) {
            $errors['current'] = 'Mot de passe actuel incorrect.';
        }
        if (text_length($new) < self::MIN_PASSWORD) {
            $errors['new'] = 'Au moins ' . self::MIN_PASSWORD . ' caractères.';
        } elseif ($new !== $confirm) {
            $errors['confirm'] = 'Les deux saisies ne correspondent pas.';
        }
        if ($errors !== []) {
            return $errors;
        }
        $_SESSION['admin_pw'] = self::fingerprint(self::setPassword($adminId, $new));
        Session::regenerate();
        FormGuard::clear('admin-password');
        self::log('mot-de-passe.change');
        return [];
    }

    /**
     * Nouveau mot de passe sans l'ancien (script de secours sur le serveur) : toutes les sessions fermées, blocage levé.
     * Avec $resetTwoFactor : double authentification à enrôler de nouveau (téléphone perdu).
     */
    public static function resetPassword(int $adminId, string $new, bool $resetTwoFactor): void
    {
        self::setPassword($adminId, $new);
        if ($resetTwoFactor) {
            Db::run('UPDATE admins SET totp_secret = NULL, totp_enabled_at = NULL, totp_last_step = NULL WHERE id = ?', [$adminId]);
            Db::run('DELETE FROM admin_recovery_codes WHERE admin_id = ?', [$adminId]);
        }
        self::log($resetTwoFactor ? 'mot-de-passe.reinitialise.2fa' : 'mot-de-passe.reinitialise', '', $adminId);
    }

    /**
     * Mot de passe oublié : jeton aléatoire de 256 bits, seul son sha256 est gardé ; un nouveau lien annule les précédents.
     * Au plus RESET_PER_ACCOUNT liens par compte et par quart d'heure.
     * @return array{0: string, 1: string}|null [jeton, e-mail], null si rien à envoyer (l'appelant répond pareil)
     */
    public static function issueReset(string $email): ?array
    {
        $admin = Db::run('SELECT id, email FROM admins WHERE email = ?', [strtolower(trim($email))])->fetch();
        if (!$admin) {
            return null;
        }
        $recent = (int) Db::run('SELECT COUNT(*) FROM admin_password_tokens WHERE admin_id = ? AND created_at >= ?', [$admin['id'], date('Y-m-d H:i:s', time() - 900)])->fetchColumn();
        if ($recent >= self::RESET_PER_ACCOUNT) {
            return null;
        }
        $token = bin2hex(random_bytes(32));
        Db::transaction(static function () use ($admin, $token): void {
            Db::run('UPDATE admin_password_tokens SET used_at = ? WHERE admin_id = ? AND used_at IS NULL', [now(), $admin['id']]);
            Db::run('INSERT INTO admin_password_tokens (admin_id, token_hash, expires_at, created_at) VALUES (?, ?, ?, ?)',
                [$admin['id'], hash('sha256', $token), date('Y-m-d H:i:s', time() + self::RESET_MINUTES * 60), now()]);
        });
        self::log('mot-de-passe.lien', '', (int) $admin['id']);
        return [$token, (string) $admin['email']];
    }

    /**
     * Lien de mot de passe pour un compte donné (invitation par modules/comptes-admin-invitation, 7 jours par exemple).
     * Les liens précédents du compte sont annulés. Le jeton n'est jamais stocké, seule son empreinte sha256.
     * @return string jeton à placer dans l'adresse nouveau-mot-de-passe/?jeton=
     */
    public static function issueLink(int $adminId, int $minutes): string
    {
        $token = bin2hex(random_bytes(32));
        Db::transaction(static function () use ($adminId, $token, $minutes): void {
            Db::run('UPDATE admin_password_tokens SET used_at = ? WHERE admin_id = ? AND used_at IS NULL', [now(), $adminId]);
            Db::run('INSERT INTO admin_password_tokens (admin_id, token_hash, expires_at, created_at) VALUES (?, ?, ?, ?)',
                [$adminId, hash('sha256', $token), date('Y-m-d H:i:s', time() + max(1, $minutes) * 60), now()]);
        });
        self::log('mot-de-passe.lien', '', $adminId);
        return $token;
    }

    public static function resetIsValid(string $token): bool
    {
        return preg_match('/^[a-f0-9]{64}$/', $token) === 1
            && (bool) Db::run('SELECT 1 FROM admin_password_tokens WHERE token_hash = ? AND used_at IS NULL AND expires_at > ?', [hash('sha256', $token), now()])->fetchColumn();
    }

    /**
     * Lien consommé par une écriture conditionnelle (un seul usage, même en cas de double envoi), puis nouveau mot de passe.
     * Toutes les sessions du compte sont fermées ; la double authentification reste demandée à la connexion.
     * @return array<string, string> erreurs par champ (password, confirm, token), vide si le mot de passe est changé
     */
    public static function completeReset(string $token, string $new, string $confirm): array
    {
        if (preg_match('/^[a-f0-9]{64}$/', $token) !== 1) {
            return ['token' => 'Ce lien n\'est plus valable.'];
        }
        if (text_length($new) < self::MIN_PASSWORD) {
            return ['password' => 'Au moins ' . self::MIN_PASSWORD . ' caractères.'];
        }
        if ($new !== $confirm) {
            return ['confirm' => 'Les deux saisies ne correspondent pas.'];
        }
        $hash = hash('sha256', $token);
        $adminId = (int) Db::run('SELECT admin_id FROM admin_password_tokens WHERE token_hash = ?', [$hash])->fetchColumn();
        $consumed = $adminId !== 0
            && Db::run('UPDATE admin_password_tokens SET used_at = ? WHERE token_hash = ? AND used_at IS NULL AND expires_at > ?', [now(), $hash, now()])->rowCount() === 1;
        if (!$consumed) {
            return ['token' => 'Ce lien n\'est plus valable.'];
        }
        self::setPassword($adminId, $new);
        self::log('mot-de-passe.reinitialise.lien', '', $adminId);
        return [];
    }

    /**
     * Liens expirés ou utilisés (tâche planifiée). Ceux du dernier quart d'heure restent, inutilisables, pour la limite par compte.
     * @return int nombre supprimé
     */
    public static function purgeResetTokens(): int
    {
        return Db::run('DELETE FROM admin_password_tokens WHERE (used_at IS NOT NULL OR expires_at <= ?) AND created_at < ?', [now(), date('Y-m-d H:i:s', time() - 900)])->rowCount();
    }

    public static function log(string $action, string $target = '', ?int $adminId = null): void
    {
        $adminId ??= is_int($_SESSION['admin_id'] ?? null) ? $_SESSION['admin_id'] : ($_SESSION['admin_pending']['id'] ?? null);
        Db::run('INSERT INTO admin_log (admin_id, action, target, ip, created_at) VALUES (?, ?, ?, ?, ?)',
            [$adminId, $action, $target, PHP_SAPI === 'cli' ? 'serveur' : Request::ip(), now()]);
    }

    // ---------------------------------------------------------------- interne

    /** Empreinte du hachage gardée en session : un nouveau mot de passe invalide toutes les sessions ouvertes avec l'ancien. */
    private static function fingerprint(string $passwordHash): string
    {
        return Crypto::mac('admin-session|' . $passwordHash);
    }

    private static function complete(int $id, string $action = 'connexion'): void
    {
        Session::regenerate();
        Csrf::rotate();
        unset($_SESSION['admin_pending']);
        $_SESSION['admin_id'] = $id;
        $_SESSION['admin_seen'] = time();
        $_SESSION['admin_since'] = time();
        $_SESSION['admin_pw'] = self::fingerprint((string) Db::run('SELECT password_hash FROM admins WHERE id = ?', [$id])->fetchColumn());
        Db::run('UPDATE admins SET failed_logins = 0, locked_until = NULL, last_login_at = ? WHERE id = ?', [now(), $id]);
        FormGuard::clear('admin-login');
        self::log($action);
    }

    private static function forget(): void
    {
        unset($_SESSION['admin_id'], $_SESSION['admin_seen'], $_SESSION['admin_since'], $_SESSION['admin_pw'], $_SESSION['admin_pending'], $_SESSION['admin_enroll_secret']);
    }

    /** Pas déjà utilisé refusé (anti-rejeu) par une écriture conditionnelle. @param array<string, mixed> $admin */
    private static function consumeTotp(array $admin, string $code): bool
    {
        $secret = Crypto::open($admin['totp_secret'] !== null ? (string) $admin['totp_secret'] : null);
        $step = $secret === null ? null : Totp::matchStep($secret, $code);
        if ($step === null) {
            return false;
        }
        return Db::run('UPDATE admins SET totp_last_step = ? WHERE id = ? AND (totp_last_step IS NULL OR totp_last_step < ?)', [$step, $admin['id'], $step])->rowCount() === 1;
    }

    private static function consumeRecovery(int $adminId, string $code): bool
    {
        $norm = self::normalize($code);
        if (strlen($norm) !== 10) {
            return false;
        }
        $ok = Db::run('UPDATE admin_recovery_codes SET used_at = ? WHERE admin_id = ? AND code_hash = ? AND used_at IS NULL', [now(), $adminId, Crypto::mac($norm)])->rowCount() === 1;
        if ($ok) {
            self::log('code-secours.utilise', '', $adminId);
        }
        return $ok;
    }

    private static function normalize(string $code): string
    {
        return (string) preg_replace('/[^A-Z0-9]/', '', strtoupper($code));
    }

    /**
     * Échec compté en une seule écriture (portable SQLite et MySQL : chaque CASE lit l'ancienne valeur de failed_logins).
     * Même requête pour un compte inconnu (id 0, aucune ligne touchée) ou déjà bloqué (condition fausse) : même durée.
     */
    private static function failure(int $id): void
    {
        // Seuil écrit dans la requête (constante entière) : lié en paramètre, PDO SQLite l'enverrait en texte,
        // et SQLite compare alors l'expression « failed_logins + 1 » (sans affinité) à une chaîne, toujours plus grande.
        $after = (int) self::LOCK_AFTER;
        $until = date('Y-m-d H:i:s', time() + self::LOCK_MINUTES * 60);
        Db::run(
            "UPDATE admins SET locked_until = CASE WHEN failed_logins + 1 >= $after THEN ? ELSE locked_until END,
                failed_logins = CASE WHEN failed_logins + 1 >= $after THEN 0 ELSE failed_logins + 1 END
             WHERE id = ? AND (locked_until IS NULL OR locked_until <= ?)",
            [$until, $id, now()]
        );
    }

    /** @return string nouveau hachage */
    private static function setPassword(int $adminId, string $new): string
    {
        $hash = password_hash($new, self::algo());
        Db::run('UPDATE admins SET password_hash = ?, failed_logins = 0, locked_until = NULL WHERE id = ?', [$hash, $adminId]);
        return $hash;
    }

    private static function algo(): string
    {
        return defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_DEFAULT;
    }
}
