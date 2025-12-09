<?php

namespace CapsuleLib\Core;

/**
 * Conteneur d'injection de dépendances simple.
 *
 * - Permet d'enregistrer des "factories" (callables) associées à un identifiant.
 * - Fournit des instances singleton créées à la demande via les factories.
 * - Lance une exception si un service demandé n'est pas défini.
 */
class DIContainer
{
    /**
     * Tableau associatif des factories enregistrées.
     *
     * @var array<string, callable>
     */
    private array $factories = [];

    /**
     * Instances créées et mises en cache.
     *
     * @var array<string, mixed>
     */
    private array $instances = [];

    /**
     * Enregistre une factory de service.
     *
     * @param string   $id      Identifiant du service
     * @param callable $factory Fonction factory qui reçoit ce container et retourne une instance
     * @return void
     */
    public function set(string $id, callable $factory): void
    {
        $this->factories[$id] = $factory;
    }

    /**
     * Récupère l'instance du service identifié par $id.
     * Crée l'instance via la factory si elle n'existe pas encore (singleton).
     *
     * @param string $id Identifiant du service
     * @throws \RuntimeException Si la factory n'est pas définie pour ce service
     * @return mixed Instance du service demandé
     */
    public function get(string $id)
    {
        if (!isset($this->instances[$id])) {
            if (!isset($this->factories[$id])) {
                throw new \RuntimeException("Service '$id' non défini dans le container.");
            }
            $this->instances[$id] = $this->factories[$id]($this);
        }
        return $this->instances[$id];
    }
}
