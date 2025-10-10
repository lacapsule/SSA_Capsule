<?php

declare(strict_types=1);

namespace Capsule\Contracts;

/**
 * Interface pour les réponses HTTP immutables.
 *
 * Représente une réponse HTTP avec des méthodes pour accéder et modifier
 * le statut, le corps et les en-têtes de manière immuable.
 */
interface ResponseInterface
{
    /**
     * Récupère le code de statut HTTP.
     *
     * @return int Code de statut HTTP (200, 404, etc.)
     */
    public function getStatus(): int;

    /**
     * Récupère le corps de la réponse.
     *
     * @return string|iterable<string> Corps de la réponse
     */
    public function getBody(): string|iterable;

    /**
     * Récupère tous les en-têtes de la réponse.
     *
     * @return array<string, list<string>> Tableau des en-têtes
     */
    public function getHeaders(): array;

    /**
     * Retourne une nouvelle instance avec le statut modifié.
     *
     * @param int $status Nouveau code de statut HTTP
     * @return self Nouvelle instance de réponse
     */
    public function withStatus(int $status): self;

    /**
     * Retourne une nouvelle instance avec le corps modifié.
     *
     * @param string|iterable<string> $body Nouveau corps de réponse
     * @return self Nouvelle instance de réponse
     */
    public function withBody(string|iterable $body): self;

    /**
     * Retourne une nouvelle instance avec un en-tête remplacé.
     *
     * @param string $name Nom de l'en-tête
     * @param string $value Valeur de l'en-tête
     * @return self Nouvelle instance de réponse
     */
    public function withHeader(string $name, string $value): self;

    /**
     * Retourne une nouvelle instance avec une valeur ajoutée à un en-tête existant.
     *
     * @param string $name Nom de l'en-tête
     * @param string $value Valeur à ajouter
     * @return self Nouvelle instance de réponse
     */
    public function withAddedHeader(string $name, string $value): self;

    /**
     * Retourne une nouvelle instance sans l'en-tête spécifié.
     *
     * @param string $name Nom de l'en-tête à supprimer
     * @return self Nouvelle instance de réponse
     */
    public function withoutHeader(string $name): self;
}
