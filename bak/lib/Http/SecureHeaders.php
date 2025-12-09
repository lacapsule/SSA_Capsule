<?php

declare(strict_types=1);

namespace CapsuleLib\Http;

/**
 * Gère l'envoi centralisé d'en-têtes HTTP liés à la sécurité.
 *
 * Fournit des méthodes pour configurer :
 * - les headers de sécurité généraux (CSP, HSTS, etc.)
 * - les réponses JSON
 * - les téléchargements forcés de fichiers
 */
class SecureHeaders
{
    /**
     * Envoie un ensemble d'en-têtes HTTP de sécurité.
     *
     * Fusionne un ensemble de valeurs par défaut (CSP, HSTS, XFO, etc.)
     * avec les valeurs personnalisées fournies dans le tableau `$headers`.
     * Les clés sont sensibles à la casse (conformes aux noms de headers HTTP).
     *
     * @param array<string,string> $headers  Tableau associatif `Header => Valeur`.
     *                                       Les valeurs personnalisées surchargent les valeurs par défaut.
     *
     * @return void
     */
    public static function send(array $headers = []): void
    {
        $default = [
            "Strict-Transport-Security" => 'max-age=31536000; includeSubDomains',
            "Content-Security-Policy" => "default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com; img-src 'self' data:;",
            "X-Frame-Options" => 'DENY',
            "X-Content-Type-Options" => 'nosniff',
            "Referrer-Policy" => 'no-referrer',
            "Permissions-Policy" => 'geolocation=(), microphone=()',
        ];

        $final = array_merge($default, $headers);

        foreach ($final as $key => $value) {
            $clean = trim(preg_replace('/[\r\n]+/', ' ', $value));
            header("$key: $clean");
        }
    }

    /**
     * Définit l'en-tête HTTP pour indiquer un contenu JSON.
     *
     * À utiliser pour les API ou les réponses Ajax.
     *
     * @return void
     */
    public static function json(): void
    {
        header('Content-Type: application/json; charset=utf-8');
    }

    /**
     * Envoie les en-têtes pour forcer le téléchargement d’un fichier.
     *
     * @param string $filename Nom du fichier tel qu’il doit apparaître dans la boîte de téléchargement.
     *
     * @return void
     */
    public static function download(string $filename): void
    {
        header('Content-Disposition: attachment; filename="' . basename($filename) . '"');
        header('Content-Type: application/octet-stream');
    }
}
