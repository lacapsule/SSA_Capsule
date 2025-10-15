<?php

declare(strict_types=1);

namespace Capsule\Domain\Service;

final class SessionAuthService
{
    private static function ensureStarted(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            ini_set('session.use_strict_mode', '1');
            ini_set('session.cookie_httponly', '1');
            ini_set('session.cookie_samesite', 'Strict');
            @session_start();
        }
    }

    public function regenerateId(): void
    {
        self::ensureStarted();
        if (!headers_sent()) {
            @session_regenerate_id(true);
        }
    }

    /** @param array{id:int,username:string,role:string,email:string} $user */
    public function setUser(array $user): void
    {
        self::ensureStarted();
        $_SESSION['admin'] = $user;
    }

    public function commit(): void
    {
        @session_write_close();
    }

    public function destroy(): void
    {
        self::ensureStarted();
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        @session_destroy();
    }
}
