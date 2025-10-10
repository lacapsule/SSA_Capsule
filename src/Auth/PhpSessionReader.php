<?php

declare(strict_types=1);

namespace Capsule\Auth;

use Capsule\Contracts\SessionReader;

final class PhpSessionReader implements SessionReader
{
    private bool $started = false;

    private function ensureStarted(): void
    {
        if ($this->started) {
            return;
        }
        if (session_status() !== PHP_SESSION_ACTIVE) {
            // Sécurise un minimum (mets secure=1 en prod HTTPS)
            ini_set('session.use_strict_mode', '1');
            ini_set('session.cookie_httponly', '1');
            ini_set('session.cookie_samesite', 'Strict');
            @session_start();
        }
        $this->started = true;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $this->ensureStarted();

        return $_SESSION[$key] ?? $default;
    }

    public function has(string $key): bool
    {
        $this->ensureStarted();

        return array_key_exists($key, $_SESSION);
    }
}
