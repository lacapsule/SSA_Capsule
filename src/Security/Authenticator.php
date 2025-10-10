<?php

declare(strict_types=1);

namespace Capsule\Security;

use PDO;

final class Authenticator
{
    private static function ensureSessionStarted(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }
        // Sécurise un minimum (mets secure=1 en prod HTTPS)
        ini_set('session.use_strict_mode', '1');
        ini_set('session.cookie_httponly', '1');
        ini_set('session.cookie_samesite', 'Strict');
        @session_start(); // ne produit rien si déjà démarrée ailleurs
    }

    private static function safeRegenerateId(): void
    {
        // Ne tente pas si les headers sont déjà envoyés (sinon warning)
        if (headers_sent()) {
            return; // fallback: on garde l’ID courant (pas idéal mais pas d’erreur)
        }
        // session doit être active
        self::ensureSessionStarted();
        @session_regenerate_id(true);
    }

    public static function login(PDO $pdo, string $username, string $password): bool
    {
        $stmt = $pdo->prepare('SELECT id, username, role, email, password_hash FROM users WHERE username = :username');
        $stmt->execute(['username' => $username]);
        $user = $stmt->fetch(\PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password_hash'])) {
            self::ensureSessionStarted();
            self::safeRegenerateId(); // éviter fixation de session

            $_SESSION['admin'] = [
                'id' => (int)$user['id'],
                'username' => (string)$user['username'],
                'role' => (string)$user['role'],
                'email' => (string)$user['email'],
            ];

            // Libère le verrou de session avant redirection
            @session_write_close();

            return true;
        }

        return false;
    }

    public static function logout(): void
    {
        self::ensureSessionStarted();

        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }

        @session_destroy();
    }
}
