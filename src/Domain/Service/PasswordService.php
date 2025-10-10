<?php

declare(strict_types=1);

namespace Capsule\Domain\Service;

use Capsule\Domain\Repository\UserRepository;

/**
 * Service unique "PasswordService" : règles + hash + change user password.
 * Périmètre volontairement limité au mot de passe.
 */
final class PasswordService
{
    /**
     * @param array<string,mixed> $hashOptions
     * Options passées à password_hash/password_needs_rehash (ex: ['cost' => 12])
     */
    public function __construct(
        private UserRepository $users,
        private int $minLength = 8,
        private array $hashOptions = []
    ) {
    }

    /**
     * Validation métier du nouveau mdp.
     *
     * @return string[] Liste d’erreurs (vide = OK)
     */
    public function validate(string $new, ?string $old = null): array
    {
        $e = [];
        if (\strlen($new) < $this->minLength) {
            $e[] = "Le mot de passe doit contenir au moins {$this->minLength} caractères.";
        }
        if ($old !== null && $old === $new) {
            $e[] = "Le nouveau mot de passe doit être différent de l'ancien.";
        }

        // Ajoute ici tes règles (classes de caractères, blacklist, etc.)
        return $e;
    }

    /** Hash/verify/rehash — encapsule les fonctions natives PHP. */
    public function hash(string $plain): string
    {
        return \password_hash($plain, \PASSWORD_DEFAULT, $this->hashOptions);
    }

    public function verify(string $plain, string $hash): bool
    {
        return \password_verify($plain, $hash);
    }

    public function needsRehash(string $hash): bool
    {
        return \password_needs_rehash($hash, \PASSWORD_DEFAULT, $this->hashOptions);
    }

    /**
     * Orchestration : vérifie l'ancien, valide le nouveau, met à jour le hash.
     *
     * @return array{0: bool, 1: string[]} Tuple [ok, errors]
     */
    public function changePassword(int $userId, string $old, string $new): array
    {
        $errors = $this->validate($new, $old);
        if ($errors) {
            return [false, $errors];
        }

        $currentHash = $this->users->getPasswordHashById($userId);
        if ($currentHash === null) {
            return [false, ['Utilisateur introuvable.']];
        }
        if (!$this->verify($old, $currentHash)) {
            return [false, ['Ancien mot de passe invalide.']];
        }

        $newHash = $this->hash($new);
        $ok = $this->users->updatePasswordHash($userId, $newHash);

        return [$ok, $ok ? [] : ['Erreur lors de la mise à jour du mot de passe.']];
    }

    /**
     * Rehash transparent au login si l’algo/options ont évolué.
     * Appelle-la dans ton flux de login après verify().
     */
    public function maybeRehashOnLogin(int $userId, string $plain, string $currentHash): void
    {
        if ($this->needsRehash($currentHash)) {
            $this->users->updatePasswordHash($userId, $this->hash($plain));
        }
    }
}
