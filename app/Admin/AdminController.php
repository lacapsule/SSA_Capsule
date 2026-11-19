<?php
declare(strict_types=1);

namespace App\Admin;

use App\Core\Csrf;
use App\Core\Db;
use App\Core\FormGuard;
use App\Core\Outbox;
use App\Core\Request;
use App\Core\Session;
use App\Core\View;

/**
 * Écrans de connexion et de sécurité du back-office : connexion, code, enrôlement, codes de secours,
 * mot de passe oublié, nouveau mot de passe, réglages de sécurité, déconnexion. Routes : app/Admin/routes-auth.php.
 */
final class AdminController
{
    /** Adresse d'un écran du back-office (config admin.base, '/admin' par défaut). */
    public static function url(string $path = ''): string
    {
        return rtrim((string) config('admin.base', '/admin'), '/') . '/' . ltrim($path, '/');
    }

    /**
     * Page demandée avant la connexion (?retour=, posé par AdminAuth::require()) : chemin local du back-office seulement,
     * jamais une autre origine (//exemple.org, /\exemple.org, schéma, caractères de contrôle). Null sinon.
     */
    public static function safeReturn(mixed $value): ?string
    {
        if (!is_string($value) || $value === '' || strlen($value) > 2000) {
            return null;
        }
        $base = rtrim(self::url(), '/') . '/';
        if (!str_starts_with($value, $base) || str_starts_with($value, '//') || str_contains($value, '\\')
            || preg_match('/[[:cntrl:]]/', $value) === 1) {
            return null;
        }
        return $value;
    }

    /** Après la connexion : la page demandée au départ (gardée en session le temps des étapes), sinon l'accueil. */
    private static function afterLogin(): string
    {
        $retour = self::safeReturn($_SESSION['admin_retour'] ?? null);
        unset($_SESSION['admin_retour']);
        return $retour ?? self::url();
    }

    /** @param array<string, mixed> $data */
    private static function renderAuth(string $template, array $data, string $title): void
    {
        Session::resume();
        header('Cache-Control: no-store');
        View::render("admin/$template", $data + ['title' => $title, 'flashes' => Session::takeFlashes()], 'admin/auth-layout');
    }

    /** Coque du back-office (gabarits/tableau-de-bord-php) si elle est installée, sinon la carte des écrans de connexion. */
    private static function shellLayout(): string
    {
        $layout = (string) config('admin.layout', 'admin/layout');
        return is_file(ROOT . '/templates/' . $layout . '.php') ? $layout : 'admin/auth-layout';
    }

    // ---------------------------------------------------------------- connexion

    public static function login(): void
    {
        $retour = self::safeReturn($_GET['retour'] ?? null);
        if (AdminAuth::current() !== null) {
            redirect($retour ?? self::url());
        }
        if ($retour !== null) {
            Session::start();
            $_SESSION['admin_retour'] = $retour;
        }
        $old = Session::old('admin-login');
        self::renderAuth('login', ['error' => $old['_error'] ?? null, 'email' => (string) ($old['email'] ?? '')], 'Connexion');
    }

    public static function doLogin(): void
    {
        Csrf::check();
        $email = (string) ($_POST['email'] ?? '');
        $result = AdminAuth::password($email, (string) ($_POST['password'] ?? ''));
        if ($result === 'verify') {
            redirect(self::url('verification/'));
        }
        if ($result === 'enroll') {
            redirect(self::url('double-authentification/'));
        }
        Session::old('admin-login', ['email' => $email, '_error' => $result]);
        redirect(self::url('connexion/'));
    }

    public static function verify(): void
    {
        if (AdminAuth::pending() === null) {
            Session::flash('L\'étape de vérification a expiré : reconnectez-vous.', 'info');
            redirect(self::url('connexion/'));
        }
        $old = Session::old('admin-verify');
        self::renderAuth('verify', ['error' => $old['_error'] ?? null, 'backup' => isset($_GET['secours'])], 'Vérification');
    }

    public static function doVerify(): void
    {
        Csrf::check();
        if (AdminAuth::verify((string) ($_POST['code'] ?? ''))) {
            redirect(self::afterLogin());
        }
        if (AdminAuth::pending() === null) {
            Session::flash('Trop d\'essais ou délai dépassé : reconnectez-vous.', 'error');
            redirect(self::url('connexion/'));
        }
        Session::old('admin-verify', ['_error' => 'Code incorrect ou déjà utilisé. Attendez le code suivant et réessayez.']);
        redirect(self::url('verification/') . (isset($_POST['backup']) ? '?secours' : ''));
    }

    public static function enroll(): void
    {
        $admin = AdminAuth::pending();
        $secret = AdminAuth::enrollSecret();
        if ($admin === null || $secret === null) {
            redirect(self::url('connexion/'));
        }
        $issuer = (string) (config('admin.issuer') ?: config('app.name', 'Back-office'));
        $old = Session::old('admin-enroll');
        self::renderAuth('enroll', [
            'secret' => trim(chunk_split($secret, 4, ' ')),
            'uri' => Totp::uri($secret, (string) $admin['email'], $issuer),
            'issuer' => $issuer,
            'error' => $old['_error'] ?? null,
            'qr' => true,
            'wide' => true,
        ], 'Double authentification');
    }

    public static function doEnroll(): void
    {
        Csrf::check();
        $codes = AdminAuth::enroll((string) ($_POST['code'] ?? ''));
        if ($codes === null) {
            if (AdminAuth::pending() === null) {
                Session::flash('Trop d\'essais ou délai dépassé : reconnectez-vous.', 'error');
                redirect(self::url('connexion/'));
            }
            Session::old('admin-enroll', ['_error' => 'Code incorrect. Vérifiez l\'heure du téléphone et saisissez le code affiché maintenant.']);
            redirect(self::url('double-authentification/'));
        }
        $_SESSION['admin_codes'] = $codes;
        redirect(self::url('codes-de-secours/'));
    }

    /** Codes de secours montrés une seule fois, juste après l'enrôlement. */
    public static function recoveryCodes(): void
    {
        AdminAuth::require();
        $codes = $_SESSION['admin_codes'] ?? null;
        unset($_SESSION['admin_codes']);
        if (!is_array($codes)) {
            redirect(self::url());
        }
        self::renderAuth('codes', ['codes' => $codes, 'suite' => self::afterLogin(), 'wide' => true], 'Codes de secours');
    }

    public static function logout(): void
    {
        Csrf::check();
        AdminAuth::logout();
        Session::flash('Vous êtes déconnecté.', 'info');
        redirect(self::url('connexion/'));
    }

    // ---------------------------------------------------------------- mot de passe oublié

    public static function forgot(): void
    {
        self::renderAuth('forgot', ['sent' => isset($_GET['envoye'])], 'Mot de passe oublié');
    }

    /**
     * Même réponse, même délai, que le compte existe ou non : l'e-mail est mis en file et part après la réponse.
     * Limites : 5 demandes par IP et par quart d'heure, 3 liens par compte (AdminAuth::issueReset).
     */
    public static function sendForgot(): void
    {
        Csrf::check();
        if (!FormGuard::rateLimited('admin-reset', 5, 15)) {
            self::mailResetLink((string) ($_POST['email'] ?? ''));
        }
        redirect(self::url('mot-de-passe-oublie/') . '?envoye=1');
    }

    public static function reset(): void
    {
        // Jeton dans l'adresse : jamais transmis à un autre site par le Referer, jamais mis en cache.
        header('Referrer-Policy: no-referrer');
        $token = (string) ($_GET['jeton'] ?? '');
        $old = Session::old('admin-reset');
        self::renderAuth('reset', ['token' => $token, 'valid' => AdminAuth::resetIsValid($token), 'errors' => $old], 'Nouveau mot de passe');
    }

    public static function saveReset(): void
    {
        Csrf::check();
        $token = (string) ($_POST['jeton'] ?? '');
        $errors = AdminAuth::completeReset($token, (string) ($_POST['password'] ?? ''), (string) ($_POST['confirm'] ?? ''));
        if ($errors !== []) {
            Session::old('admin-reset', $errors);
            redirect(self::url('nouveau-mot-de-passe/') . '?jeton=' . rawurlencode($token));
        }
        Session::flash('Mot de passe enregistré. Connectez-vous avec lui, puis saisissez le code de votre application (à la première connexion, vous la configurez).');
        redirect(self::url('connexion/'));
    }

    /** @return bool vrai si un e-mail a été mis en file */
    private static function mailResetLink(string $email): bool
    {
        $base = self::publicUrl();
        $issued = $base !== null ? AdminAuth::issueReset($email) : null;
        if ($issued === null) {
            return false;
        }
        [$token, $to] = $issued;
        $link = $base . self::url('nouveau-mot-de-passe/') . '?jeton=' . $token;
        $site = (string) config('app.name', 'le site');
        Outbox::queue($to, 'Nouveau mot de passe du back-office', "Bonjour,\n\n"
            . "Pour choisir un nouveau mot de passe du back-office de $site, ouvrez ce lien (valable " . AdminAuth::RESET_MINUTES . " minutes, un seul usage) :\n$link\n\n"
            . "La double authentification restera demandée à la connexion.\n\n"
            . "Si vous n'êtes pas à l'origine de cette demande, ignorez ce message : votre mot de passe ne change pas.\n", null, null, 0);
        Outbox::flushAfterResponse();
        return true;
    }

    /** Adresse publique pour les liens envoyés par e-mail : APP_URL, sinon l'hôte courant en développement seulement. */
    private static function publicUrl(): ?string
    {
        $env = rtrim((string) ($_ENV['APP_URL'] ?? ''), '/');
        if ($env !== '') {
            return $env;
        }
        if (is_local() && Request::host() !== '') {
            return (Request::isHttps() ? 'https://' : 'http://') . Request::host();
        }
        error_log('APP_URL vide dans le .env : lien de mot de passe non envoyé.');
        return null;
    }

    // ---------------------------------------------------------------- réglages de sécurité

    public static function securityPage(): void
    {
        $admin = Admin::page();
        $newCodes = $_SESSION['admin_new_codes'] ?? null;
        unset($_SESSION['admin_new_codes']);
        header('Cache-Control: no-store');
        View::render('admin/settings-securite', [
            'title' => 'Sécurité du compte',
            'admin' => $admin,
            'recoveryLeft' => AdminAuth::recoveryLeft((int) $admin['id']),
            'log' => Db::run('SELECT action, target, ip, created_at FROM admin_log WHERE admin_id = ? ORDER BY id DESC LIMIT 15', [$admin['id']])->fetchAll(),
            'pwErrors' => Session::old('admin-pw'),
            'newCodes' => is_array($newCodes) ? $newCodes : null,
            'flashes' => Session::takeFlashes(),
            'wide' => true,
        ], self::shellLayout());
    }

    public static function security(): void
    {
        $admin = Admin::action();
        $action = (string) ($_POST['action'] ?? '');
        if ($action === 'codes') {
            $_SESSION['admin_new_codes'] = AdminAuth::regenerateRecovery((int) $admin['id']);
            AdminAuth::log('codes-secours.regeneres');
            Session::flash('Nouveaux codes de secours créés : les anciens ne fonctionnent plus.');
        } elseif ($action === 'password') {
            $errors = AdminAuth::changePassword((int) $admin['id'], (string) ($_POST['current'] ?? ''), (string) ($_POST['new'] ?? ''), (string) ($_POST['confirm'] ?? ''));
            $errors === [] ? Session::flash('Mot de passe changé. Vos autres sessions ouvertes sont fermées.') : Session::old('admin-pw', $errors);
        } elseif ($action === 'password-link') {
            $sent = !FormGuard::rateLimited('admin-reset', 5, 15) && self::mailResetLink((string) $admin['email']);
            Session::flash($sent
                ? 'Lien envoyé à ' . $admin['email'] . ' : valable ' . AdminAuth::RESET_MINUTES . ' minutes, un seul usage.'
                : 'Lien non envoyé : trop de demandes récentes, réessayez dans un quart d\'heure.', $sent ? 'success' : 'error');
        } elseif ($action === 'reenroll') {
            AdminAuth::startReenroll();
            redirect(self::url('double-authentification/'));
        }
        redirect(self::url('securite/') . ($action === 'password' ? '#mot-de-passe' : ''));
    }
}
