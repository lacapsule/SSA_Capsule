<?php

declare(strict_types=1);

namespace Capsule\Domain\DTO;

/**
 * User Data Transfer Object (DTO)
 *
 * Représente un utilisateur de façon immuable et typée.
 * Sert uniquement au transport de données, sans logique métier ni accès base de données.
 */
class UserDTO
{
    /**
     * @param int    $id            Identifiant unique de l'utilisateur
     * @param string $username      Nom d'utilisateur unique
     * @param string $password_hash Hash du mot de passe (stocké sécurisé)
     * @param string $role          Rôle utilisateur (ex: "admin", "user")
     * @param string $email         Adresse email de l'utilisateur
     * @param string $created_at    Date de création du compte (format string)
     */
    public function __construct(
        public readonly int $id,
        public readonly string $username,
        public readonly string $password_hash,
        public readonly string $role,
        public readonly string $email,
        public readonly string $created_at,
    ) {
    }
}
