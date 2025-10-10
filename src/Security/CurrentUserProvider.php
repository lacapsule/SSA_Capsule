<?php

declare(strict_types=1);

namespace Capsule\Security;

/**
 * Fournisseur d'informations sur l'utilisateur courant.
 *
 * Fournit des méthodes utilitaires pour accéder aux informations
 * de l'utilisateur authentifié et vérifier ses permissions.
 *
 * @final
 */
final class CurrentUserProvider
{
    /**
     * Retourne l'utilisateur courant depuis la session, ou null si invité.
     *
     * @return array{id?: int, username?: string, role?: string, email?: string}|null
     *         Données utilisateur ou null si non authentifié
     */
    public static function getUser(): ?array
    {
        if (session_status() !== \PHP_SESSION_ACTIVE) {
            session_start();
        }

        return $_SESSION['admin'] ?? null;
    }

    /**
     * Vérifie si un utilisateur est actuellement authentifié.
     *
     * @return bool True si un utilisateur est authentifié, false sinon
     */
    public static function isAuthenticated(): bool
    {
        return self::getUser() !== null;
    }

    /**
     * Vérifie si l'utilisateur donné est admin (helper pratique pour templates).
     *
     * @param array{id?: int, username?: string, role?: string}|null $user
     *        Utilisateur à vérifier (utilise l'utilisateur courant si null)
     * @return bool True si l'utilisateur est admin, false sinon
     */
    public static function isAdmin(?array $user): bool
    {
        $user = $user ?? self::getUser();

        return ($user['role'] ?? null) === 'admin';
    }
}
