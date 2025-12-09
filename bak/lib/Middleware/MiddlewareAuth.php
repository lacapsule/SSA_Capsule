<?php

declare(strict_types=1);

namespace CapsuleLib\Middleware;

final class MiddlewareAuth
{
    /** Auth obligatoire pour /dashboard/... */
    public static function auth(): callable
    {
        return function (array $params, callable $next) {
            if (session_status() !== PHP_SESSION_ACTIVE) {
                session_start();
            }

            $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

            // 1) Ne rien faire hors /dashboard
            if (!str_starts_with($path, '/dashboard')) {
                return $next($params);
            }

            // 2) Whitelist défensive
            if ($path === '/login' || $path === '/logout') {
                return $next($params);
            }

            // 3) Auth
            if (empty($_SESSION['admin'])) {
                header('Location: /login', true, 302);
                exit;
            }

            return $next($params);
        };
    }

    /** Rôle requis (ex: admin) pour /dashboard/... */
    public static function role(string $role): callable
    {
        return function (array $params, callable $next) use ($role) {
            if (session_status() !== PHP_SESSION_ACTIVE) {
                session_start();
            }

            $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

            if (!str_starts_with($path, '/dashboard')) {
                return $next($params);
            }

            if ($path === '/login' || $path === '/logout') {
                return $next($params);
            }

            $user = $_SESSION['admin'] ?? null;
            if (!$user || ($user['role'] ?? null) !== $role) {
                header('Location: /login', true, 302);
                exit;
            }

            return $next($params);
        };
    }
}
