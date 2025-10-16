<?php

declare(strict_types=1);

namespace Capsule\Auth;

use Capsule\Contracts\SessionReader;
use Capsule\Http\Support\SessionManager;

/**
 * PhpSessionReader - Implémentation du contrat SessionReader
 *
 * Délègue tout à SessionManager
 */
final class PhpSessionReader implements SessionReader
{
    public function get(string $key, mixed $default = null): mixed
    {
        return SessionManager::get($key, $default);
    }

    public function has(string $key): bool
    {
        return SessionManager::has($key);
    }
}
