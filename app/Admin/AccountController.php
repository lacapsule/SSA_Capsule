<?php
declare(strict_types=1);

namespace App\Admin;

use App\Core\Auth;
use App\Core\Db;
use App\Core\Outbox;
use App\Core\Session;
use App\Core\View;

/**
 * Comptes du back-office sur la table admins (modules/authentification-admin) : liste, invitation, nouveau lien,
 * modification, suppression. Aucun mot de passe ne transite : la personne reçoit un lien à usage unique
 * (AdminAuth::issueLink) et le choisit elle-même ; à sa première connexion, elle active la double authentification.
 * Avec modules/roles-et-journal : rôle et périmètre par compte, écrans réservés aux administrateurs, journal.
 */
final class AccountController
{
    public const INVITE_DAYS = 7;
    public const RESET_HOURS = 48;

    public static function index(): void
    {
        self::guard();
        $accounts = Db::run('SELECT * FROM admins ORDER BY LOWER(name), LOWER(email)')->fetchAll();
        self::render('admin/comptes/index', ['title' => 'Comptes', 'accounts' => array_map(self::row(...), $accounts)]);
    }

    public static function create(): void
    {
        self::guard();
        self::form(null, []);
    }

    public static function store(): void
    {
        self::guard(true);
        $input = self::input();
        $errors = self::validate($input, null);
        if ($errors !== []) {
            self::form(null, $errors, $input);
            return;
        }
        // Mot de passe vide : aucune connexion possible tant que le lien d'invitation n'a pas servi.
        $id = Db::insert('INSERT INTO admins (email, name, password_hash, created_at) VALUES (?, ?, ?, ?)', [$input['email'], $input['name'], '', now()]);
        if (self::hasRoles()) {
            Db::run('UPDATE admins SET role = ?, perimetre = ? WHERE id = ?', [$input['role'], $input['perimetre'], $id]);
        }
        self::log('compte.creation', $input['email']);
        $sent = self::sendLink(self::find((string) $id), true);
        Session::flash($sent
            ? 'Compte de ' . $input['name'] . ' créé. Une invitation est partie vers ' . $input['email'] . ' (lien valable ' . self::INVITE_DAYS . ' jours).'
            : 'Compte de ' . $input['name'] . ' créé, mais l\'invitation n\'a pas pu partir : renvoyez-la depuis la fiche du compte.', $sent ? 'success' : 'error');
        redirect(Admin::url('comptes/'));
    }

    public static function edit(string $id): void
    {
        self::guard();
        self::form(self::find($id), []);
    }

    public static function update(string $id): void
    {
        self::guard(true);
        $account = self::find($id);
        $input = self::input();
        $errors = self::validate($input, (int) $account['id']);
        if (self::hasRoles() && $input['role'] !== 'admin' && ($account['role'] ?? 'admin') === 'admin' && self::adminCount() <= 1) {
            $errors['role'] = 'Il doit rester au moins un administrateur.';
        }
        if ($errors !== []) {
            self::form($account, $errors, $input);
            return;
        }
        Db::run('UPDATE admins SET name = ?, email = ? WHERE id = ?', [$input['name'], $input['email'], $account['id']]);
        if (self::hasRoles()) {
            Db::run('UPDATE admins SET role = ?, perimetre = ? WHERE id = ?', [$input['role'], $input['perimetre'], $account['id']]);
        }
        self::log('compte.modification', $input['email']);
        Session::flash('Compte de ' . $input['name'] . ' mis à jour.');
        redirect(Admin::url('comptes/'));
    }

    public static function destroy(string $id): void
    {
        $me = self::guard(true);
        $account = self::find($id);
        if ((int) $account['id'] === (int) $me['id']) {
            Session::flash('Vous ne pouvez pas supprimer votre propre compte.', 'error');
        } elseif ((int) Db::run('SELECT COUNT(*) FROM admins')->fetchColumn() <= 1) {
            Session::flash('Il doit rester au moins un compte.', 'error');
        } elseif (self::hasRoles() && ($account['role'] ?? 'admin') === 'admin' && self::adminCount() <= 1) {
            Session::flash('Il doit rester au moins un administrateur.', 'error');
        } else {
            // Liens et codes de secours supprimés en cascade ; le journal garde l'adresse (colonne actor).
            Db::run('DELETE FROM admins WHERE id = ?', [$account['id']]);
            self::log('compte.suppression', (string) $account['email']);
            Session::flash('Compte de ' . ($account['name'] ?: $account['email']) . ' supprimé.');
        }
        redirect(Admin::url('comptes/'));
    }

    /** Nouveau lien : invitation si le mot de passe n'est pas encore choisi, sinon changement (l'actuel reste valable). */
    public static function passwordLink(string $id): void
    {
        self::guard(true);
        $account = self::find($id);
        $invite = (string) $account['password_hash'] === '';
        if (self::sendLink($account, $invite)) {
            self::log('compte.lien', (string) $account['email']);
            Session::flash('Lien envoyé à ' . $account['email'] . ($invite ? '.' : '. Le mot de passe actuel reste valable tant que le nouveau n\'est pas choisi.'));
        } else {
            Session::flash('Le lien n\'a pas pu partir : vérifiez APP_URL et les réglages d\'envoi des e-mails.', 'error');
        }
        redirect(Admin::url('comptes/' . (int) $account['id'] . '/'));
    }

    /** Rôles et périmètres disponibles (modules/roles-et-journal installé et migration 032 appliquée). */
    public static function hasRoles(): bool
    {
        static $has = null;
        if ($has === null) {
            try {
                $has = class_exists(Auth::class) && Db::run('SELECT role, perimetre FROM admins WHERE 1 = 0') instanceof \PDOStatement;
            } catch (\PDOException) {
                $has = false;
            }
        }
        return $has;
    }

    /** @param array<string, mixed> $account */
    private static function sendLink(array $account, bool $invite): bool
    {
        $base = rtrim((string) ($_ENV['APP_URL'] ?? ''), '/');
        if ($base === '') {
            error_log('APP_URL vide dans le .env : lien de compte non envoyé.');
            return false;
        }
        $token = AdminAuth::issueLink((int) $account['id'], $invite ? self::INVITE_DAYS * 1440 : self::RESET_HOURS * 60);
        $url = $base . AdminController::url('nouveau-mot-de-passe/') . '?jeton=' . $token;
        $site = (string) config('app.name', 'le site');
        $sender = (string) (Admin::current()['name'] ?? '');
        $first = explode(' ', trim((string) ($account['name'] ?: 'Bonjour')))[0];
        $validity = $invite ? self::INVITE_DAYS . ' jours' : self::RESET_HOURS . ' heures';
        $subject = $invite ? 'Votre accès à l\'administration de ' . $site : 'Nouveau mot de passe pour l\'administration de ' . $site;
        $data = [
            'subject' => $subject, 'is_invite' => $invite, 'first_name' => $first, 'sender' => $sender, 'email' => (string) $account['email'],
            'url' => $url, 'validity' => $validity, 'site_name' => $site, 'login_url' => $base . AdminController::url('connexion/'),
        ];
        $text = implode("\n", [
            "Bonjour $first,",
            '',
            $invite
                ? ($sender !== '' ? "$sender vous a créé un compte" : 'Un compte vous a été créé') . " sur l'administration de $site."
                : "Un nouveau mot de passe a été demandé pour votre compte d'administration de $site.",
            $invite ? 'Pour l\'activer, choisissez votre mot de passe en ouvrant ce lien :' : 'Pour le choisir, ouvrez ce lien :',
            $url,
            '',
            "Ce lien est personnel, valable $validity et ne fonctionne qu'une seule fois.",
            'Ensuite, connectez-vous sur ' . $data['login_url'] . ' avec votre e-mail (' . $account['email'] . ') et ce mot de passe'
                . ($invite ? ', puis activez la double authentification avec une application de votre téléphone.' : '.'),
            '',
            $invite ? 'Si vous n\'attendiez pas ce message, ignorez-le.' : 'Si vous n\'êtes pas à l\'origine de cette demande, ignorez ce message : votre mot de passe actuel reste valable.',
        ]) . "\n";
        try {
            // Différé de 0 seconde et envoyé après la réponse : la page n'attend pas le serveur SMTP.
            Outbox::queue((string) $account['email'], $subject, $text, View::capture('emails/comptes/invitation', $data), null, 0);
            Outbox::flushAfterResponse();
            return true;
        } catch (\Throwable $e) {
            error_log('Comptes : invitation non mise en file : ' . $e->getMessage());
            return false;
        }
    }

    /** Écrans des comptes : réservés aux administrateurs si les rôles existent. POST : origine, connexion, jeton. */
    private static function guard(bool $post = false): array
    {
        if ($post) {
            $admin = Admin::action();
            if (self::hasRoles() && $admin['role'] !== 'admin') {
                abort(403);
            }
            return $admin;
        }
        return self::hasRoles() ? Admin::requireAdmin() : Admin::page();
    }

    private static function log(string $action, string $target): void
    {
        self::hasRoles() ? Auth::log($action, $target) : AdminAuth::log($action, $target);
    }

    /** @return array<string, mixed> */
    private static function find(string $id): array
    {
        $account = ctype_digit($id) ? Db::run('SELECT * FROM admins WHERE id = ?', [(int) $id])->fetch() : false;
        if (!$account) {
            abort(404, 'Ce compte n\'existe plus.');
        }
        return $account;
    }

    /**
     * Ligne de la liste, sans secret.
     * @param array<string, mixed> $a
     * @return array<string, mixed>
     */
    private static function row(array $a): array
    {
        return [
            'id' => (int) $a['id'],
            'name' => (string) $a['name'],
            'email' => (string) $a['email'],
            'role' => (string) ($a['role'] ?? 'admin'),
            'perimetre' => isset($a['perimetre']) && $a['perimetre'] !== '' ? (string) $a['perimetre'] : null,
            'pending' => (string) $a['password_hash'] === '',
            'two_factor' => $a['totp_enabled_at'] !== null,
            'last_login_at' => $a['last_login_at'],
        ];
    }

    private static function adminCount(): int
    {
        return (int) Db::run("SELECT COUNT(*) FROM admins WHERE role = 'admin'")->fetchColumn();
    }

    /** @return array{name: string, email: string, role: string, perimetre: ?string} */
    private static function input(): array
    {
        $role = ($_POST['role'] ?? '') === 'editor' ? 'editor' : 'admin';
        $perimetre = $role === 'editor' ? trim((string) ($_POST['perimetre'] ?? '')) : '';
        return [
            'name' => trim((string) preg_replace('/\s+/u', ' ', (string) ($_POST['name'] ?? ''))),
            'email' => strtolower(trim((string) ($_POST['email'] ?? ''))),
            'role' => $role,
            'perimetre' => $perimetre === '' ? null : $perimetre,
        ];
    }

    /**
     * @param array{name: string, email: string, role: string, perimetre: ?string} $input
     * @return array<string, string>
     */
    private static function validate(array $input, ?int $ignoreId): array
    {
        $errors = [];
        if ($input['name'] === '' || text_length($input['name']) > 120) {
            $errors['name'] = 'Indiquez le prénom et le nom (120 caractères au plus).';
        }
        if (filter_var($input['email'], FILTER_VALIDATE_EMAIL) === false || text_length($input['email']) > 190) {
            $errors['email'] = 'Indiquez une adresse e-mail valide, par exemple nom@exemple.fr : elle sert à se connecter.';
        } elseif (Db::run('SELECT 1 FROM admins WHERE email = ? AND id <> ?', [$input['email'], $ignoreId ?? 0])->fetchColumn()) {
            $errors['email'] = 'Cette adresse est déjà utilisée par un autre compte.';
        }
        if (self::hasRoles() && $input['perimetre'] !== null && !array_key_exists($input['perimetre'], Auth::perimeters())) {
            $errors['perimetre'] = 'Choisissez un périmètre dans la liste.';
        }
        return $errors;
    }

    /**
     * @param array<string, mixed>|null $account
     * @param array<string, string> $errors
     * @param array<string, mixed>|null $input
     */
    private static function form(?array $account, array $errors, ?array $input = null): void
    {
        if ($errors !== []) {
            http_response_code(422);
        }
        $me = Admin::current();
        $row = $account === null ? null : self::row($account);
        self::render('admin/comptes/form', [
            'title' => $account === null ? 'Nouveau compte' : 'Modifier le compte',
            'account' => $row,
            'own' => $row !== null && $me !== null && $row['id'] === (int) $me['id'],
            'values' => $input ?? $row ?? ['name' => '', 'email' => '', 'role' => 'editor', 'perimetre' => null],
            'errors' => $errors,
            'roles' => self::hasRoles(),
            'perimeters' => self::hasRoles() ? Auth::perimeters() : [],
        ]);
    }

    /** @param array<string, mixed> $data */
    private static function render(string $template, array $data): void
    {
        View::render($template, $data, (string) config('admin.layout', 'admin/layout'));
    }
}
