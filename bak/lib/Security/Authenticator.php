<?php

declare(strict_types=1);

namespace CapsuleLib\Security;

use PDO;

/**
 * Classe d’authentification utilisateur.
 *
 * Gère la connexion/déconnexion et la vérification d'état d'authentification.
 * Stocke les données essentielles de session pour l’utilisateur connecté.
 */
class Authenticator
{
    /**
     * Tente de connecter un utilisateur avec un nom d'utilisateur et un mot de passe.
     *
     * Vérifie le mot de passe hashé en base, initialise la session sécurisée.
     *
     * @param PDO    $pdo      Instance PDO connectée à la base de données.
     * @param string $username Nom d'utilisateur soumis.
     * @param string $password Mot de passe clair soumis.
     * @return bool  True si authentification réussie, false sinon.
     */
    public static function login(PDO $pdo, string $username, string $password): bool
    {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username");
        $stmt->execute(['username' => $username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['admin'] = [
                'id'       => $user['id'],
                'username' => $user['username'],
                'role'     => $user['role'],
                'email'    => $user['email'],
            ];
            return true;
        }

        return false;
    }

    /**
     * Déconnecte l'utilisateur courant en détruisant la session.
     *
     * Vide la session, supprime le cookie et détruit la session serveur.
     *
     * @return void
     */
    public static function logout(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        session_destroy();
    }
}
