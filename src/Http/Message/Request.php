<?php

declare(strict_types=1);

namespace Capsule\Http\Message;

/**
 * Représentation immuable d'une requête HTTP.
 *
 * Cette classe encapsule toutes les informations d'une requête HTTP
 * de manière sécurisée et normalisée.
 *
 * @final
 */
final class Request
{
    /**
     * Constructeur de la requête HTTP.
     *
     * @param string $method Méthode HTTP (GET, POST, etc.)
     * @param string $path Chemin de la requête (normalisé)
     * @param array<int,mixed> $query Paramètres de requête ($_GET)
     * @param array<string,string> $headers En-têtes HTTP
     * @param array<int,mixed> $cookies Cookies de la requête
     * @param array<int,mixed> $server Variables serveur ($_SERVER)
     * @param string $scheme Protocole (http ou https)
     * @param string|null $host Hôte de la requête
     * @param int|null $port Port de la requête
     * @param string|null $rawBody Corps brut de la requête
     */
    public function __construct(
        public readonly string $method,
        public readonly string $path,
        public readonly array $query,
        public readonly array $headers,
        public readonly array $cookies,
        public readonly array $server,
        public readonly string $scheme = 'http',
        public readonly ?string $host = null,
        public readonly ?int $port = null,
        public readonly ?string $rawBody = null
    ) {
    }

    /**
     * Crée une instance de Request à partir des superglobales PHP.
     *
     * Cette méthode normalise et sécurise les données des superglobales
     * pour créer une représentation cohérente de la requête.
     *
     * @return self Instance de Request
     */
    public static function fromGlobals(): self
    {
        $srv = $_SERVER;

        // 1) Méthode (uppercase, fallback GET)
        $method = strtoupper($srv['REQUEST_METHOD'] ?? 'GET');
        if (!preg_match('/^[A-Z]+$/', $method)) {
            $method = 'GET';
        }

        // 2) Path normalisé (sans query, root par défaut, sécurité)
        $uri = (string)($srv['REQUEST_URI'] ?? '/');
        $path = strtok($uri, '?') ?: '/';
        // bloque null bytes et directory traversal naïf
        if (str_contains($path, "\0")) {
            $path = '/';
        }
        // decode percent-encoding SANS transformer '+' en espace (RFC3986)
        $path = rawurldecode($path);
        // optionnel: compacter les doubles slashes (sauf préfixe)
        $path = preg_replace('#//+#', '/', $path) ?? $path;

        // 3) En-têtes (sans getallheaders)
        $headers = [];
        foreach ($srv as $k => $v) {
            if (str_starts_with($k, 'HTTP_')) {
                $name = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($k, 5)))));
                $headers[$name] = self::sanitizeHeaderValue((string)$v);
            } elseif (in_array($k, ['CONTENT_TYPE', 'CONTENT_LENGTH', 'CONTENT_MD5'], true)) {
                $name = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', $k))));
                $headers[$name] = self::sanitizeHeaderValue((string)$v);
            }
        }

        // 4) Scheme/host/port (ne PAS faire confiance aux X-Forwarded-* par défaut)
        $https = ($srv['HTTPS'] ?? '') && strtolower((string)$srv['HTTPS']) !== 'off';
        $scheme = $https ? 'https' : 'http';
        $host = $headers['Host'] ?? ($srv['SERVER_NAME'] ?? null) ?? null;
        $port = isset($srv['SERVER_PORT']) ? (int)$srv['SERVER_PORT'] : null;

        // 5) Raw body (utile pour JSON)
        $rawBody = file_get_contents('php://input') ?: null;

        return new self(
            method: $method,
            path: $path,
            query: $_GET,
            headers: $headers,
            cookies: $_COOKIE,
            server: $srv,
            scheme: $scheme,
            host: $host,
            port: $port,
            rawBody: $rawBody,
        );
    }

    /**
     * Nettoie une valeur d'en-tête pour empêcher l'injection.
     *
     * @param string $v Valeur d'en-tête à nettoyer
     * @return string Valeur nettoyée
     */
    private static function sanitizeHeaderValue(string $v): string
    {
        // Empêche l'injection d'en-têtes
        return str_replace(["\r", "\n"], '', $v);
    }
}
