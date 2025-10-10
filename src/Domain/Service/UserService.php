<?php

declare(strict_types=1);

namespace Capsule\Domain\Service;

use Capsule\Domain\Repository\UserRepository;
use Capsule\Domain\DTO\UserDTO;
use RuntimeException;

/**
 * Service métier pour la gestion des utilisateurs.
 *
 * Fournit des méthodes pour créer, mettre à jour, récupérer et vérifier
 * les utilisateurs via le UserRepository.
 */
class UserService
{
    private UserRepository $userRepository;

    /**
     * Constructeur injectant un UserRepository.
     *
     * @param UserRepository $userRepository Instance du repository utilisateur.
     */
    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    /**
     * Crée un nouvel utilisateur avec un mot de passe hashé.
     *
     * Vérifie que le nom d’utilisateur et l’email n’existent pas déjà,
     * sinon lève une RuntimeException.
     *
     * @param string $username Nom d’utilisateur souhaité.
     * @param string $password Mot de passe en clair.
     * @param string $email Adresse email.
     * @param string $role Rôle utilisateur (défaut 'employee').
     * @return int ID de l’utilisateur créé.
     *
     * @throws RuntimeException si username ou email existe déjà.
     */
    public function createUser(string $username, string $password, string $email, string $role = 'employee'): int
    {
        if ($this->userRepository->existsUsername($username)) {
            throw new RuntimeException('Username already exists');
        }
        if ($this->userRepository->existsEmail($email)) {
            throw new RuntimeException('Email already exists');
        }
        $hash = password_hash($password, PASSWORD_DEFAULT);

        return $this->userRepository->insert([
            'username' => $username,
            'password_hash' => $hash,
            'role' => $role,
            'email' => $email,
        ]);
    }

    /**
     * Supprime un utilisateur par son ID.
     *
     * @param int $userId ID de l’utilisateur.
     * @return bool Succès de la suppression.
     */
    public function deleteUser(int $userId): bool
    {
        return $this->userRepository->delete($userId) > 0;
    }

    /**
     * Met à jour le mot de passe d’un utilisateur (hash sécurisé).
     *
     * @param int $userId ID de l’utilisateur.
     * @param string $newPassword Nouveau mot de passe en clair.
     * @return bool Succès de la mise à jour.
     */
    public function updatePassword(int $userId, string $newPassword): bool
    {
        $hash = password_hash($newPassword, PASSWORD_DEFAULT);

        return $this->userRepository->update($userId, ['password_hash' => $hash]);
    }

    /**
     * Vérifie si un utilisateur a le rôle d’administrateur.
     *
     * @param UserDTO $user Objet utilisateur.
     * @return bool True si rôle admin, false sinon.
     */
    public function isAdmin(UserDTO $user): bool
    {
        return $user->role === 'admin';
    }

    /**
     * Récupère un utilisateur par son nom d’utilisateur.
     *
     * @param string $username Nom d’utilisateur.
     * @return UserDTO|null DTO utilisateur ou null si non trouvé.
     */
    public function getUserByUsername(string $username): ?UserDTO
    {
        return $this->userRepository->findByUsername($username);
    }

    /**
     * Récupère un utilisateur par son ID.
     *
     * @param int $id ID utilisateur.
     * @return UserDTO|null DTO utilisateur ou null si non trouvé.
     */
    public function getUserById(int $id): ?UserDTO
    {
        return $this->userRepository->findById($id);
    }

    /**
     * Récupère tous les utilisateurs.
     *
     * @return UserDTO[] Tableau d’objets DTO utilisateurs.
     */
    public function getAllUsers(): array
    {
        return $this->userRepository->allUsers();
    }

    /**
     * @param array<string,mixed> $data
     */
    public function updateUser(int $id, array $data): bool
    {
        return $this->userRepository->update($id, $data);
    }
}
