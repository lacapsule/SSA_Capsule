<?php
declare(strict_types=1);

namespace App\Admin;

use App\Core\Csrf;

/**
 * Façade unique de la garde d'administration, appelée par tous les modules :
 *   $admin = Admin::page();    // écran GET du back-office : connexion exigée, en-têtes privés
 *   $admin = Admin::action();  // envoi POST : origine, connexion puis jeton CSRF (modules/securite-formulaires)
 * La garde vient de config('admin.guard') (nom de classe) ou de Admin::use(new ...Guard()).
 */
final class Admin
{
    private static ?AdminGuard $guard = null;

    public static function use(AdminGuard $guard): void
    {
        self::$guard = $guard;
    }

    public static function guard(): AdminGuard
    {
        if (self::$guard === null) {
            $class = (string) config('admin.guard', '');
            if ($class === '' || !is_a($class, AdminGuard::class, true)) {
                throw new \LogicException('Aucune garde d\'administration : renseigner admin.guard dans config/app.php.');
            }
            self::$guard = new $class();
        }
        return self::$guard;
    }

    /**
     * Compte connecté, complété aux clés communes (rôle 'admin' et périmètre null pour une garde sans rôles).
     * @return array{id: int, email: string, name: string, role: string, perimetre: ?string}|null
     */
    public static function current(): ?array
    {
        $admin = self::guard()->current();
        if ($admin === null) {
            return null;
        }
        return [
            'id' => (int) $admin['id'],
            'email' => (string) $admin['email'],
            'name' => (string) ($admin['name'] ?? ''),
            'role' => ($admin['role'] ?? 'admin') === 'editor' ? 'editor' : 'admin',
            'perimetre' => isset($admin['perimetre']) && $admin['perimetre'] !== '' ? (string) $admin['perimetre'] : null,
        ] + $admin;
    }

    /** Préfixe du back-office, sans slash final ('/admin' par défaut). */
    public static function base(): string
    {
        return rtrim((string) config('admin.base', '/admin'), '/');
    }

    /** Adresse d'un écran du back-office : Admin::url('messages/') donne /admin/messages/. */
    public static function url(string $path = ''): string
    {
        return self::base() . '/' . ltrim($path, '/');
    }

    /**
     * Écran du back-office. Sans connexion : 401 en JSON, sinon redirection vers la connexion (retour gardé en GET).
     * @return array{id: int, email: string, name: string, role: string, perimetre: ?string}
     */
    public static function page(): array
    {
        self::privateHeaders();
        $admin = self::current();
        if ($admin !== null) {
            return $admin;
        }
        if (wants_json()) {
            json_response(['ok' => false, 'message' => 'Session expirée : reconnectez-vous.'], 401);
        }
        $login = self::guard()->loginUrl();
        $method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
        redirect($method === 'GET' ? $login . '?retour=' . rawurlencode(current_path()) : $login);
    }

    /**
     * Envoi POST du back-office. L'origine est vérifiée avant tout (403 pour un autre site, connecté ou non),
     * puis la connexion, puis le jeton : jeton périmé, saisie rendue au formulaire $form (Csrf::field($form)).
     * @return array{id: int, email: string, name: string, role: string, perimetre: ?string}
     */
    public static function action(): array
    {
        Csrf::checkOrigin();
        $admin = self::page();
        Csrf::check();
        return $admin;
    }

    /** Rôle administrateur (les gardes sans rôles répondent toujours oui). */
    public static function isAdmin(): bool
    {
        return (self::current()['role'] ?? '') === 'admin';
    }

    /** Écran réservé aux administrateurs (comptes, journal, réglages) : 403 pour un éditeur. */
    public static function requireAdmin(): array
    {
        $admin = self::page();
        if ($admin['role'] !== 'admin') {
            abort(403);
        }
        return $admin;
    }

    /** Pages privées : jamais en cache, jamais indexées, référent limité au site (un jeton d'URL ne fuit pas). */
    public static function privateHeaders(): void
    {
        if (headers_sent()) {
            return;
        }
        header('Cache-Control: no-store');
        header('X-Robots-Tag: noindex, nofollow');
        header('Referrer-Policy: same-origin');
    }
}
