<?php

declare(strict_types=1);

namespace Capsule\Contracts;

use Capsule\Http\Message\Response;

/**
 * Factory pour créer différents types de réponses HTTP.
 *
 * Fournit des méthodes utilitaires pour créer des réponses standardisées
 * avec les en-têtes appropriés pour chaque type de contenu.
 */
interface ResponseFactoryInterface
{
    /**
     * Crée une réponse HTTP basique.
     *
     * @param int $status Code de statut HTTP (défaut: 200)
     * @param string|iterable<string> $body Corps de la réponse (défaut: chaîne vide)
     * @return Response Instance de réponse configurée
     */
    public function createResponse(int $status = 200, string|iterable $body = ''): Response;

    /**
     * Crée une réponse JSON.
     *
     * @param array<string,mixed>|\JsonSerializable $data Données à sérialiser en JSON
     * @param int $status Code de statut HTTP (défaut: 200)
     * @return Response Réponse avec en-tête Content-Type: application/json
     */
    public function json(array|\JsonSerializable $data, int $status = 200): Response;

    /**
     * Crée une réponse texte brut.
     *
     * @param string $body Contenu texte
     * @param int $status Code de statut HTTP (défaut: 200)
     * @return Response Réponse avec en-tête Content-Type: text/plain
     */
    public function text(string $body, int $status = 200): Response;

    /**
     * Crée une réponse HTML.
     *
     * @param string $body Contenu HTML
     * @param int $status Code de statut HTTP (défaut: 200)
     * @return Response Réponse avec en-tête Content-Type: text/html
     */
    public function html(string $body, int $status = 200): Response;

    /**
     * Crée une redirection HTTP.
     *
     * @param string $location URL de destination
     * @param int $status Code de statut HTTP (défaut: 302)
     * @return Response Réponse de redirection avec en-tête Location
     */
    public function redirect(string $location, int $status = 302): Response;

    /**
     * Crée une réponse de problème (RFC 7807).
     *
     * @param array<string,mixed> $problem Données du problème
     * @param int $status Code de statut HTTP (défaut: 400)
     * @return Response Réponse avec en-tête Content-Type: application/problem+json
     */
    public function problem(array $problem, int $status = 400): Response;

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
    ): Response;

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
    ): Response;

    /**
     * Crée une réponse JSON en flux continu.
     *
     * @param iterable<mixed> $items Éléments à sérialiser en JSON
     * @param callable|null $toRow Fonction de transformation optionnelle
     * @return Response Réponse JSON en streaming
     */
    public function jsonStream(iterable $items, ?callable $toRow = null): Response;

    /**
     * Crée une réponse 201 Created.
     *
     * @param string $location URL de la ressource créée
     * @param array<string,mixed>|\JsonSerializable|null $body Corps optionnel de la réponse
     * @return Response Réponse 201 avec en-tête Location
     */
    public function created(string $location, array|\JsonSerializable|null $body = null): Response;

    /**
     * Crée une réponse vide (sans corps).
     *
     * @param int $status Code de statut HTTP (défaut: 204)
     * @return Response Réponse sans corps
     */
    public function empty(int $status = 204): Response;

    /**
     * Ajoute un cookie à une réponse existante.
     *
     * @param Response $r Réponse à modifier
     * @param \Capsule\Http\Support\Cookie $cookie Cookie à ajouter
     * @return Response Nouvelle instance de réponse avec le cookie
     */
    public function withCookie(Response $r, \Capsule\Http\Support\Cookie $cookie): Response;

    /**
     * Ajoute des en-têtes pour empêcher la mise en cache.
     *
     * @param Response $r Réponse à modifier
     * @return Response Nouvelle instance de réponse avec en-têtes no-cache
     */
    public function noCache(Response $r): Response;
}
