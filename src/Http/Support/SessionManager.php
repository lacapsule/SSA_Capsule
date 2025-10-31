<?php

declare(strict_types=1);

namespace Capsule\Http\Support;

/**
 * SessionManager - Gestion centralisée des sessions PHP
 *
 * Responsabilités :
 * - Démarrage unique et sécurisé de la session
 * - Accès unifié aux données de session
 * - Regénération d'ID (anti-fixation)
 * - Destruction propre
 *
 * Invariants :
 * - La session est démarrée au plus une fois (idempotence)
 * - Configuration sécurisée appliquée avant session_start()
 * - Thread-safe pour requêtes concurrentes (verrou PHP natif)
 *
 * Sécurité :
 * - httponly=1, samesite=Strict (défense XSS/CSRF)
 * - strict_mode=1 (refuse IDs non générés par PHP)
 */

final class SessionManager
{
    private static bool $started = false;

    /**
     * Démarre la session si pas encore active (idempotent)
     *
     * @throws \RuntimeException si session_start() échoue
     */
    public static function start(): void
    {
        if (self::$started || session_status() === \PHP_SESSION_ACTIVE) {
            return;
        }

        // Configuration sécurisée AVANT démarrage
        ini_set('session.use_strict_mode', '1');
        ini_set('session.cookie_httponly', '1');
        ini_set('session.cookie_samesite', 'Strict');
        // NOTE: prod:
        ini_set('session.cookie_secure', '1');

        if (!@session_start()) {
            throw new \RuntimeException('Failed to start session');
        }

        self::$started = true;
    }

    /**
     * Vérifie si la session est active
     */
    public static function isActive(): bool
    {
        return session_status() === \PHP_SESSION_ACTIVE;
    }

    /**
     * Regénère l'ID de session (anti-fixation)
     * Appeler après login réussi
     */
    public static function regenerateId(): void
    {
        self::start();

        if (!headers_sent()) {
            @session_regenerate_id(true);
        }
    }

    /**
     * Récupère une valeur de session
     *
     * @template T
     * @param T $default
     * @return mixed|T
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        self::start();

        return $_SESSION[$key] ?? $default;
    }

    /**
     * Définit une valeur de session
     */
    public static function set(string $key, mixed $value): void
    {
        self::start();
        $_SESSION[$key] = $value;
    }

    /**
     * Vérifie si une clé existe en session
     */
    public static function has(string $key): bool
    {
        self::start();

        return array_key_exists($key, $_SESSION);
    }

    /**
     * Supprime une clé de session
     */
    public static function remove(string $key): void
    {
        self::start();
        unset($_SESSION[$key]);
    }

    /**
     * Ferme l'écriture de session (libère le verrou fichier/DB)
     * Utile avant opérations longues (emails, API externes, etc.)
     */
    public static function commit(): void
    {
        if (self::isActive()) {
            @session_write_close();
        }
    }

    /**
     * Détruit complètement la session (logout)
     * - Vide $_SESSION
     * - Supprime le cookie
     * - Appelle session_destroy()
     */
    public static function destroy(): void
    {
        self::start();

        $_SESSION = [];

        // Supprimer le cookie côté client
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                (bool) $params['secure'],
                (bool) $params['httponly']
            );
        }

        @session_destroy();
        self::$started = false;
    }

    /**
     * Récupère toutes les données de session
     *
     * @return array<string,mixed>
     */
    public static function all(): array
    {
        self::start();

        return $_SESSION;
    }

    /**
     * Vide toutes les données de session SANS détruire la session
     * (utile pour switch de contexte utilisateur)
     */
    public static function clear(): void
    {
        self::start();
        $_SESSION = [];
    }
}
