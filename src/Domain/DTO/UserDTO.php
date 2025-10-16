<?php

declare(strict_types=1);

namespace Capsule\Domain\DTO;

/**
 * UserDTO - Objet de transfert pour un utilisateur
 *
 * Immutable : toutes les propriétés sont readonly
 * Thread-safe : peut être partagé entre requêtes sans risque
 *
 * ⚠️ Ne JAMAIS exposer passwordHash à l'extérieur du domaine Auth
 */
final class UserDTO
{
    public function __construct(
        public readonly int $id,
        public readonly string $username,
        public readonly string $passwordHash,  // ✅ Cohérent avec hydrate()
        public readonly string $role,
        public readonly string $email,
        public readonly ?string $createdAt = null,
    ) {
    }

    /**
     * Convertit le DTO en array pour la session
     * Exclut le passwordHash pour des raisons de sécurité
     *
     * @return array{id: int, username: string, role: string, email: string}
     */
    public function toSessionArray(): array
    {
        return [
            'id' => $this->id,
            'username' => $this->username,
            'role' => $this->role,
            'email' => $this->email,
        ];
    }

    /**
     * Vérifie si l'utilisateur est admin
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Convertit en array complet (pour logs, debug)
     *
     * @return array<string,mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'username' => $this->username,
            'role' => $this->role,
            'email' => $this->email,
            'createdAt' => $this->createdAt,
            // passwordHash intentionnellement omis
        ];
    }
}
