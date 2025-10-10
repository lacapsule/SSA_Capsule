<?php

declare(strict_types=1);

namespace Capsule\Contracts;

/**
 * Interface pour la lecture sécurisée des données de session.
 *
 * Fournit un accès en lecture seule aux données de session
 * avec des valeurs par défaut pour éviter les erreurs.
 */
interface SessionReader
{
    /**
     * Récupère une valeur de session.
     *
     * @param string $key Clé de la session
     * @param mixed $default Valeur par défaut si la clé n'existe pas
     * @return mixed Valeur de la session ou valeur par défaut
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * Vérifie si une clé existe dans la session.
     *
     * @param string $key Clé à vérifier
     * @return bool True si la clé existe, false sinon
     */
    public function has(string $key): bool;
}
