<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Session PHP posée seulement quand elle sert (compte, panier, formulaire avec erreur) : cookie strictement nécessaire, sans bannière.
 * Cookie HttpOnly, Secure en HTTPS, SameSite (config session.samesite, Lax par défaut), mode strict (identifiant inconnu refusé).
 */
final class Session
{
    public static function name(): string
    {
        return (string) config('session.name', 'app_session');
    }

    public static function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }
        $sameSite = ucfirst(strtolower((string) config('session.samesite', 'Lax')));
        session_name(self::name());
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'secure' => Request::isHttps(),
            'httponly' => true,
            'samesite' => in_array($sameSite, ['Lax', 'Strict'], true) ? $sameSite : 'Lax',
        ]);
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.use_trans_sid', '0');
        ini_set('session.gc_maxlifetime', (string) (int) config('session.lifetime', 7200));
        session_start();
    }

    /** Reprend une session existante sans en créer : une page publique ne pose aucun cookie. */
    public static function resume(): void
    {
        if (isset($_COOKIE[self::name()])) {
            self::start();
        }
    }

    /** Nouvel identifiant (connexion, changement de droits) : l'ancien ne vaut plus rien (fixation de session). */
    public static function regenerate(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
    }

    /** Fin de session complète : données effacées et cookie retiré. */
    public static function destroy(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return;
        }
        $_SESSION = [];
        $p = session_get_cookie_params();
        setcookie(self::name(), '', ['expires' => time() - 3600, 'path' => $p['path'], 'secure' => $p['secure'], 'httponly' => true, 'samesite' => $p['samesite'] ?: 'Lax']);
        session_destroy();
    }

    /**
     * Notification affichée à la page suivante (composant notifications).
     * @param 'success'|'error'|'info' $type
     * @param array{url: string, token: string, back?: string}|null $undo action annulable : la notification propose « Annuler »
     */
    public static function flash(string $message, string $type = 'success', ?array $undo = null): void
    {
        self::start();
        $_SESSION['flash'][] = ['message' => $message, 'type' => $type] + ($undo !== null ? ['undo' => $undo] : []);
    }

    /** @return list<array{message: string, type: string, undo?: array{url: string, token: string, back?: string}}> */
    public static function takeFlashes(): array
    {
        $flashes = $_SESSION['flash'] ?? [];
        unset($_SESSION['flash']);
        return $flashes;
    }

    /**
     * Saisie d'un formulaire gardée pour la page suivante (erreur, formulaire expiré) : rien n'est perdu.
     * Avec $values : enregistre. Sans : lit une seule fois puis efface.
     * @param array<string, mixed>|null $values
     * @return array<string, mixed>
     */
    public static function old(string $form, ?array $values = null): array
    {
        if ($values !== null) {
            self::start();
            $_SESSION['old'][$form] = $values;
            return $values;
        }
        $values = $_SESSION['old'][$form] ?? [];
        unset($_SESSION['old'][$form]);
        return is_array($values) ? $values : [];
    }
}
