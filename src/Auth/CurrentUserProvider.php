<?php

declare(strict_types=1);

namespace Capsule\Auth;

use Capsule\Http\Support\SessionManager;

/**
 * CurrentUserProvider - Helper statique pour l'utilisateur courant
 *
 * Simplifie l'accès à l'utilisateur en session
 */
final class CurrentUserProvider
{
    /**
     * @return array{id:int,username:string,role:string,email:string}|null
     */
    public static function getUser(): ?array
    {
        return SessionManager::get('admin');
    }

    public static function isAuthenticated(): bool
    {
        return SessionManager::has('admin');
    }

    public static function hasRole(string $role): bool
    {
        $user = self::getUser();

        return $user && ($user['role'] ?? null) === $role;
    }

    public static function isAdmin(): bool
    {
        return self::hasRole('admin');
    }
}
