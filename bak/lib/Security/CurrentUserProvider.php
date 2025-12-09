<?php

declare(strict_types=1);

namespace CapsuleLib\Security;

final class CurrentUserProvider
{
    /**
     * Retourne l'utilisateur courant depuis la session, ou null si invité.
     *
     * @return array{id?: int, username?: string, role?: string, email?: string}|null
     */
    public static function getUser(): ?array
    {
        if (session_status() !== \PHP_SESSION_ACTIVE) {
            session_start();
        }
        return $_SESSION['admin'] ?? null;
    }

    public static function isAuthenticated(): bool
    {
        return self::getUser() !== null;
    }

    /**
     * Vérifie si l'utilisateur donné est admin (helper pratique pour templates).
     *
     * @param array{id?: int, username?: string, role?: string}|null $user
     */
    public static function isAdmin(?array $user): bool
    {
        return ($user['role'] ?? null) === 'admin';
    }
}
