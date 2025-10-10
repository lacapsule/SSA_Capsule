<?php

declare(strict_types=1);

namespace Capsule\Http\Message;

use Capsule\Contracts\ResponseInterface;

/**
 * Représentation immuable d'une réponse HTTP.
 *
 * Cette classe implémente l'interface ResponseInterface et fournit
 * des méthodes pour manipuler le statut, le corps et les en-têtes
 * de manière immuable.
 *
 * @final
 */
final class Response implements ResponseInterface
{
    private HeaderBag $headers;

    /**
     * Constructeur de la réponse HTTP.
     *
     * @param int $status Code de statut HTTP (défaut: 200)
     * @param string|iterable<string> $body Corps de la réponse (défaut: chaîne vide)
     * @throws \InvalidArgumentException Si le statut HTTP est invalide
     */
    public function __construct(
        private int $status = 200,
        private string|iterable $body = '',
    ) {
        if ($status < 100 || $status > 599) {
            throw new \InvalidArgumentException("Invalid HTTP status: $status");
        }
        $this->headers = new HeaderBag();
    }

    /**
     * Vérifie si un en-tête existe (insensible à la casse).
     *
     * @param string $name Nom de l'en-tête
     * @return bool True si l'en-tête existe, false sinon
     */
    public function hasHeader(string $name): bool
    {
        // Case-insensitive per RFC
        foreach ($this->headers->all() as $n => $_) {
            if (strcasecmp($n, $name) === 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Récupère toutes les valeurs d'un en-tête.
     *
     * @param string $name Nom de l'en-tête
     * @return list<string> Liste des valeurs de l'en-tête
     */
    public function getHeader(string $name): array
    {
        foreach ($this->headers->all() as $n => $values) {
            if (strcasecmp($n, $name) === 0) {
                return $values;
            }
        }

        return [];
    }

    /**
     * Récupère une ligne d'en-tête combinée.
     *
     * @param string $name Nom de l'en-tête
     * @return string Valeurs combinées séparées par des virgules
     */
    public function getHeaderLine(string $name): string
    {
        $values = $this->getHeader($name);

        return $values ? implode(', ', $values) : '';
    }

    /**
     * Retourne une nouvelle instance avec l'en-tête remplacé.
     *
     * @param string $name Nom de l'en-tête
     * @param string $value Valeur de l'en-tête
     * @return self Nouvelle instance de réponse
     */
    public function withHeader(string $name, string $value): self
    {
        $c = clone $this;
        $c->headers = clone $this->headers;
        $c->headers->set($name, $value);

        return $c;
    }

    /**
     * Retourne une nouvelle instance avec une valeur ajoutée à l'en-tête.
     *
     * @param string $name Nom de l'en-tête
     * @param string $value Valeur à ajouter
     * @return self Nouvelle instance de réponse
     */
    public function withAddedHeader(string $name, string $value): self
    {
        $c = clone $this;
        $c->headers = clone $this->headers;
        $c->headers->add($name, $value);

        return $c;
    }

    /**
     * Retourne une nouvelle instance sans l'en-tête spécifié.
     *
     * @param string $name Nom de l'en-tête à supprimer
     * @return self Nouvelle instance de réponse
     */
    public function withoutHeader(string $name): self
    {
        $c = clone $this;
        $c->headers = clone $this->headers;
        $c->headers->remove($name);

        return $c;
    }

    /**
     * Retourne une nouvelle instance avec le statut modifié.
     *
     * @param int $status Nouveau code de statut HTTP
     * @return self Nouvelle instance de réponse
     * @throws \InvalidArgumentException Si le statut HTTP est invalide
     */
    public function withStatus(int $status): self
    {
        if ($status < 100 || $status > 599) {
            throw new \InvalidArgumentException("Invalid HTTP status: $status");
        }
        $c = clone $this;
        $c->status = $status;

        return $c;
    }

    /**
     * Retourne une nouvelle instance avec le corps modifié.
     *
     * @param string|iterable<string> $body Nouveau corps de réponse
     * @return self Nouvelle instance de réponse
     */
    public function withBody(string|iterable $body): self
    {
        $c = clone $this;
        $c->body = $body;

        return $c;
    }

    /**
     * Récupère le code de statut HTTP.
     *
     * @return int Code de statut HTTP
     */
    public function getStatus(): int
    {
        return $this->status;
    }

    /**
     * Récupère le corps de la réponse.
     *
     * @return string|iterable<string> Corps de la réponse
     */
    public function getBody(): string|iterable
    {
        return $this->body;
    }

    /**
     * Récupère tous les en-têtes de la réponse.
     *
     * @return array<string,list<string>> Tableau des en-têtes
     */
    public function getHeaders(): array
    {
        return $this->headers->all();
    }
}
