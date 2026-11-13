<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Protection des envois (POST) contre la falsification de requête intersite.
 * - check() : origine (Origin, sinon Referer) puis jeton de session, pour les formulaires d'une session ouverte (compte, back-office).
 * - checkOrigin() : origine seule, pour les formulaires publics sans session (avec FormGuard).
 * Jeton périmé (session expirée) mais origine correcte : la saisie est gardée et la personne revient au formulaire
 * avec un message, au lieu d'une page d'erreur qui fait tout perdre.
 */
final class Csrf
{
    public const FIELD = '_csrf';

    /**
     * Refus : callable(int $status, string $message), par exemple une page d'erreur du site. Par défaut abort().
     * @var (callable(int, string): void)|null
     */
    public static $onReject = null;

    public static function token(): string
    {
        Session::start();
        if (!isset($_SESSION['csrf']) || !is_string($_SESSION['csrf'])) {
            $_SESSION['csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf'];
    }

    /** $form : identifiant du formulaire ('profil'), pour lui rendre la saisie si le jeton a expiré. */
    public static function field(string $form = ''): string
    {
        return '<input type="hidden" name="' . self::FIELD . '" value="' . e(self::token()) . '">'
            . ($form !== '' ? '<input type="hidden" name="' . FormGuard::FORM_FIELD . '" value="' . e($form) . '">' : '');
    }

    /** Nouveau jeton (à la connexion, avec Session::regenerate()). */
    public static function rotate(): void
    {
        Session::start();
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }

    public static function check(): void
    {
        self::checkOrigin();
        Session::start();
        $sent = $_POST[self::FIELD] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
        if (!is_string($sent) || !isset($_SESSION['csrf']) || !is_string($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $sent)) {
            self::expired();
        }
    }

    /** Refuse un envoi venu d'un autre site. Ni Origin ni Referer : refusé aussi (navigateurs actuels envoient toujours Origin en POST). */
    public static function checkOrigin(): void
    {
        if (!self::sameOrigin()) {
            self::reject(403, 'Envoi refusé : ce formulaire ne vient pas de ce site. Rechargez la page et recommencez.');
        }
    }

    public static function sameOrigin(): bool
    {
        $host = Request::host();
        $source = (string) ($_SERVER['HTTP_ORIGIN'] ?? ($_SERVER['HTTP_REFERER'] ?? ''));
        if ($host === '' || $source === '' || $source === 'null') {
            return false;
        }
        $parts = parse_url($source);
        if (!is_array($parts) || !isset($parts['host'], $parts['scheme']) || !in_array($parts['scheme'], ['http', 'https'], true)) {
            return false;
        }
        $origin = strtolower($parts['host']) . (isset($parts['port']) ? ':' . $parts['port'] : '');
        return hash_equals($host, $origin);
    }

    /** Jeton absent ou périmé : retour au formulaire, saisie gardée (sauf mots de passe), si l'on sait de quel formulaire il s'agit. */
    private static function expired(): never
    {
        $form = (string) ($_POST[FormGuard::FORM_FIELD] ?? '');
        $back = self::backPath();
        if ($form !== '' && preg_match('/^[a-z0-9_-]{1,40}$/', $form) === 1 && $back !== null) {
            Session::old($form, FormGuard::keepable($_POST) + ['_error' => 'Le formulaire a expiré. Votre saisie est conservée : vérifiez-la puis envoyez de nouveau.']);
            redirect($back);
        }
        self::reject(400, 'Formulaire expiré. Rechargez la page et recommencez.');
    }

    /** Chemin de la page d'où vient l'envoi (Referer du même site), sinon null. */
    private static function backPath(): ?string
    {
        $referer = (string) ($_SERVER['HTTP_REFERER'] ?? '');
        $parts = parse_url($referer);
        if ($referer === '' || !is_array($parts) || strtolower((string) ($parts['host'] ?? '')) . (isset($parts['port']) ? ':' . $parts['port'] : '') !== Request::host()) {
            return null;
        }
        $path = (string) ($parts['path'] ?? '/');
        return str_starts_with($path, '/') ? $path . (isset($parts['query']) ? '?' . $parts['query'] : '') : null;
    }

    private static function reject(int $status, string $message): never
    {
        if (self::$onReject !== null) {
            (self::$onReject)($status, $message);
            exit;
        }
        abort($status, $message);
    }
}
