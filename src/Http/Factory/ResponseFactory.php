<?php

declare(strict_types=1);

namespace Capsule\Http\Factory;

use Capsule\Contracts\ResponseFactoryInterface;
use Capsule\Http\Message\Response;
use Capsule\Http\Support\Cookie;

/**
 * Factory pour créer différents types de réponses HTTP.
 *
 * Implémente l'interface ResponseFactoryInterface et fournit
 * des méthodes utilitaires pour créer des réponses standardisées
 * avec les en-têtes appropriés.
 *
 * @final
 */
final class ResponseFactory implements ResponseFactoryInterface
{
    /**
     * Crée une réponse HTTP basique.
     *
     * @param int $status Code de statut HTTP (défaut: 200)
     * @param string|iterable<string> $body Corps de la réponse (défaut: chaîne vide)
     * @return Response Instance de réponse configurée
     */
    public function createResponse(int $status = 200, string|iterable $body = ''): Response
    {
        return new Response($status, $body);
    }

    /**
     * Crée une réponse JSON.
     *
     * @param array<string,mixed>|\JsonSerializable $data Données à sérialiser en JSON
     * @param int $status Code de statut HTTP (défaut: 200)
     * @return Response Réponse avec en-tête Content-Type: application/json
     */
    public function json(array|\JsonSerializable $data, int $status = 200): Response
    {
        try {
            $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            $json = json_encode(['error' => 'Invalid JSON payload'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $status = 500;
        }

        return $this->createResponse($status, (string)$json)
            ->withHeader('Content-Type', 'application/json; charset=utf-8');
    }

    /**
     * Crée une réponse texte brut.
     *
     * @param string $body Contenu texte
     * @param int $status Code de statut HTTP (défaut: 200)
     * @return Response Réponse avec en-tête Content-Type: text/plain
     */
    public function text(string $body, int $status = 200): Response
    {
        return $this->createResponse($status, $body)
            ->withHeader('Content-Type', 'text/plain; charset=utf-8')
            ->withHeader('X-Content-Type-Options', 'nosniff');
    }

    /**
     * Crée une réponse HTML.
     *
     * @param string $body Contenu HTML
     * @param int $status Code de statut HTTP (défaut: 200)
     * @return Response Réponse avec en-tête Content-Type: text/html
     */
    public function html(string $body, int $status = 200): Response
    {
        return $this->createResponse($status, $body)
            ->withHeader('Content-Type', 'text/html; charset=utf-8')
            ->withHeader('X-Content-Type-Options', 'nosniff');
    }

    /**
     * Crée une redirection HTTP.
     *
     * @param string $location URL de destination
     * @param int $status Code de statut HTTP (défaut: 302)
     * @return Response Réponse de redirection avec en-tête Location
     * @throws \InvalidArgumentException Si le statut de redirection est invalide
     */
    public function redirect(string $location, int $status = 302): Response
    {
        if (!in_array($status, [301,302,303,307,308], true)) {
            throw new \InvalidArgumentException('Redirect status must be one of 301,302,303,307,308');
        }
        self::assertHeaderValueSafe($location, 'Location');

        // Body vide (pas de "Redirecting to: ...")
        return $this->createResponse($status, '')
            ->withHeader('Location', $location)
            ->withHeader('Cache-Control', 'no-store');
    }

    /**
     * Crée une réponse de problème (RFC 7807).
     *
     * @param array<string,mixed> $problem Données du problème
     * @param int $status Code de statut HTTP (défaut: 400)
     * @return Response Réponse avec en-tête Content-Type: application/problem+json
     */
    public function problem(array $problem, int $status = 400): Response
    {
        $json = json_encode($problem, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return (new Response($status, (string)$json))
            ->withHeader('Content-Type', 'application/problem+json; charset=utf-8');
    }

    /**
     * Crée une réponse de téléchargement de fichier.
     *
     * @param string $filename Nom du fichier pour le téléchargement
     * @param string $content Contenu du fichier
     * @param string $contentType Type MIME (défaut: application/octet-stream)
     * @return Response Réponse avec en-têtes de téléchargement
     */
    public function download(
        string $filename,
        string $content,
        string $contentType = 'application/octet-stream'
    ): Response {
        [$dispValue, $dispUtf8] = self::buildContentDispositionValues($filename);

        return $this->createResponse(200, $content)
            ->withHeader('Content-Type', $contentType)
            ->withHeader('Content-Disposition', "attachment; {$dispValue}; {$dispUtf8}")
            ->withHeader('X-Content-Type-Options', 'nosniff')
            ->withHeader('Cache-Control', 'no-store');
    }

    /**
     * Crée une réponse de téléchargement de flux.
     *
     * @param string $filename Nom du fichier pour le téléchargement
     * @param iterable<string> $content Contenu du fichier sous forme de flux
     * @param string $contentType Type MIME (défaut: application/octet-stream)
     * @return Response Réponse avec en-têtes de téléchargement
     */
    public function downloadStream(
        string $filename,
        iterable $content,
        string $contentType = 'application/octet-stream'
    ): Response {
        [$dispValue, $dispUtf8] = self::buildContentDispositionValues($filename);

        return $this->createResponse(200, $content)
            ->withHeader('Content-Type', $contentType)
            ->withHeader('Content-Disposition', "attachment; {$dispValue}; {$dispUtf8}")
            ->withHeader('Cache-Control', 'no-store');
    }

    /**
     * Crée une réponse JSON en flux continu.
     *
     * @param iterable<mixed> $items Éléments à sérialiser en JSON
     * @param callable|null $toRow Fonction de transformation optionnelle
     * @return Response Réponse JSON en streaming
     */
    public function jsonStream(iterable $items, ?callable $toRow = null): Response
    {
        $toRow ??= static fn ($x) => $x;
        $iter = (function () use ($items, $toRow) {
            foreach ($items as $it) {
                yield json_encode($toRow($it), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
            }
        })();

        return $this->createResponse(200, $iter)
            ->withHeader('Content-Type', 'application/x-ndjson; charset=utf-8');
    }

    /**
     * Crée une réponse 201 Created.
     *
     * @param string $location URL de la ressource créée
     * @param array<string,mixed>|\JsonSerializable|null $body Corps optionnel de la réponse
     * @return Response Réponse 201 avec en-tête Location
     */
    public function created(string $location, array|\JsonSerializable|null $body = null): Response
    {
        self::assertHeaderValueSafe($location, 'Location');
        $res = $this->createResponse(201, $body === null ? '' : (string)json_encode(
            $body,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        ));
        $res = $res->withHeader('Location', $location);
        if ($body !== null) {
            $res = $res->withHeader('Content-Type', 'application/json; charset=utf-8');
        }

        return $res;
    }

    /**
     * Crée une réponse vide (sans corps).
     *
     * @param int $status Code de statut HTTP (défaut: 204)
     * @return Response Réponse sans corps
     * @throws \InvalidArgumentException Si le statut HTTP est invalide
     */
    public function empty(int $status = 204): Response
    {
        if ($status < 100 || $status > 599) {
            throw new \InvalidArgumentException('Invalid status');
        }

        return $this->createResponse($status, '');
    }

    /**
     * Ajoute un cookie à une réponse existante.
     *
     * @param Response $r Réponse à modifier
     * @param Cookie $cookie Cookie à ajouter
     * @return Response Nouvelle instance de réponse avec le cookie
     */
    public function withCookie(Response $r, Cookie $cookie): Response
    {
        $header = $cookie->toHeader();
        self::assertHeaderValueSafe($header, 'Set-Cookie');

        return $r->withAddedHeader('Set-Cookie', $header);
    }

    /**
     * Ajoute des en-têtes pour empêcher la mise en cache.
     *
     * @param Response $r Réponse à modifier
     * @return Response Nouvelle instance de réponse avec en-têtes no-cache
     */
    public function noCache(Response $r): Response
    {
        return $r->withHeader('Cache-Control', 'no-store, no-cache, must-revalidate')
                 ->withAddedHeader('Pragma', 'no-cache')
                 ->withHeader('Expires', '0');
    }

    /**
     * Vérifie qu'une valeur d'en-tête est sécurisée.
     *
     * @param string $v Valeur à vérifier
     * @param string $name Nom de l'en-tête
     * @throws \InvalidArgumentException Si la valeur contient des caractères dangereux
     */
    private static function assertHeaderValueSafe(string $v, string $name): void
    {
        if (str_contains($v, "\r") || str_contains($v, "\n")) {
            throw new \InvalidArgumentException("Invalid header value for {$name} (CR/LF not allowed)");
        }
        // Borne défensive (facultatif)
        if (strlen($v) > 8192) {
            throw new \InvalidArgumentException("Header value for {$name} too long");
        }
    }

    /**
     * Construit les valeurs pour l'en-tête Content-Disposition.
     *
     * @param string $filename Nom du fichier
     * @return array{string,string} [ filename=..., filename*=... ]
     */
    private static function buildContentDispositionValues(string $filename): array
    {
        // filename= → ASCII-safe + quotes escaped
        $safe = str_replace(['\\','"'], ['\\\\','\\"'], $filename);
        // filename* → UTF-8 percent-encoded
        $utf8 = rawurlencode($filename);

        $dispValue = 'filename="' . $safe . '"';
        $dispUtf8 = "filename*=UTF-8''" . $utf8;

        self::assertHeaderValueSafe($safe, 'Content-Disposition filename');
        self::assertHeaderValueSafe($utf8, 'Content-Disposition filename*');

        return [$dispValue, $dispUtf8];
    }
}
