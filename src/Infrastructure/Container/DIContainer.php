<?php

declare(strict_types=1);

namespace Capsule\Infrastructure\Container;

use Capsule\Contracts\ContainerLike;

/**
 * Conteneur d'Injection de Dépendances (DI Container)
 *
 * Implémentation simple d'un conteneur DI avec pattern singleton.
 * Permet d'enregistrer des factories et de récupérer des instances uniques.
 *
 * @package Capsule\Infrastructure\Container
 */
class DIContainer implements ContainerLike
{
    /**
     * Tableau associatif des factories enregistrées
     *
     * @var array<string, callable> Clé = identifiant service, Valeur = factory callable
     */
    private array $factories = [];

    /**
     * Cache des instances créées (pattern singleton)
     *
     * @var array<string, mixed> Clé = identifiant service, Valeur = instance
     */
    private array $instances = [];

    /**
     * Enregistre une factory pour un service
     *
     * La factory reçoit le conteneur en paramètre et retourne une instance du service.
     *
     * @param string $id Identifiant unique du service
     * @param callable $factory Fonction factory qui crée l'instance
     * @return void
     */
    public function set(string $id, callable $factory): void
    {
        $this->factories[$id] = $factory;
    }

    /**
     * Récupère une instance de service
     *
     * Crée l'instance via la factory si elle n'existe pas encore (pattern singleton).
     *
     * @param string $id Identifiant du service demandé
     * @throws \RuntimeException Si le service n'est pas enregistré
     * @return mixed Instance du service demandé
     */
    public function get(string $id): mixed
    {
        if (!isset($this->instances[$id])) {
            if (!isset($this->factories[$id])) {
                throw new \RuntimeException("Service '$id' non défini dans le container.");
            }
            $this->instances[$id] = ($this->factories[$id])($this);
        }

        return $this->instances[$id];
    }
}
