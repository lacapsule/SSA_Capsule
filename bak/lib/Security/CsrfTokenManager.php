<?php

declare(strict_types=1);

namespace CapsuleLib\Security;

/**
 * Gestionnaire CSRF (Cross-Site Request Forgery).
 *
 * Fournit un token CSRF unique par session, insertion dans les formulaires,
 * et validation lors des requêtes POST pour sécuriser contre les attaques CSRF.
 */
class CsrfTokenManager
{
    /**
     * Clé de stockage du token CSRF dans la session.
     */
    const TOKEN_KEY = '_csrf';

    /**
     * Récupère ou génère un token CSRF unique pour la session.
     *
     * @return string Token CSRF hexadécimal sécurisé.
     */
    public static function getToken(): string
    {
        if (empty($_SESSION[self::TOKEN_KEY])) {
            $_SESSION[self::TOKEN_KEY] = bin2hex(random_bytes(32));
        }
        return $_SESSION[self::TOKEN_KEY];
    }

    /**
     * Génère un champ input HTML caché contenant le token CSRF.
     *
     * À insérer dans les formulaires HTML pour protection automatique.
     *
     * @return string Champ HTML input hidden avec token CSRF.
     */
    public static function insertInput(): string
    {
        $token = self::getToken();
        return '<input type="hidden" name="_csrf" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }

    /**
     * Vérifie que le token CSRF fourni correspond au token stocké en session.
     *
     * @param string|null $token Token reçu depuis le formulaire (POST).
     * @return bool True si token valide, false sinon.
     */
    public static function checkToken(?string $token): bool
    {
        return hash_equals(self::getToken(), (string)$token);
    }

    /**
     * Exige qu’une requête POST contienne un token CSRF valide.
     *
     * Si le token est absent ou invalide, stoppe l’exécution avec une erreur 403.
     *
     * @return void
     */
    public static function requireValidToken(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $token = $_POST['_csrf'] ?? '';
            if (!self::checkToken($token)) {
                http_response_code(403);
                die('CSRF token invalid. Action refused.');
            }
        }
    }
}
