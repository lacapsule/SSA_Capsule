<?php

declare(strict_types=1);

namespace Capsule\Contracts;

/**
 * Interface pour les conteneurs d'injection de dépendances.
 *
 * Fournit une méthode simple pour récupérer des services par leur identifiant.
 * Utilisé par le framework pour la résolution de dépendances.
 *
 * @method mixed get(string $id) Récupère un service par son identifiant
 */
interface ContainerLike
{
    /**
     * Récupère un service du conteneur par son identifiant.
     *
     * @param string $id Identifiant du service (nom de classe ou alias)
     * @return mixed Instance du service demandé
     * @throws \InvalidArgumentException Si le service n'existe pas
     */
    public function get(string $id): mixed;
}
