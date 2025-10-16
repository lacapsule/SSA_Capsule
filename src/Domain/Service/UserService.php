<?php

declare(strict_types=1);

namespace Capsule\Domain\Service;

use Capsule\Domain\Repository\UserRepository;
use Capsule\Domain\DTO\UserDTO;
use RuntimeException;

class UserService
{
    private const ALLOWED_ROLES = ['employee', 'admin'];

    public function __construct(private UserRepository $userRepository)
    {
    }

    /* =======================
       ======= Queries =======
       ======================= */

    /**
     * Flux d'utilisateurs (compatible pipelines lazy).
     * @return iterable<UserDTO>
     */
    public function getAllUsersIt(): iterable
    {
        // Si ton repo renvoie déjà un iterable, tu peux `yield from` direct.
        // Ici on suppose allUsers() renvoie un array<UserDTO> => on l'expose en flux.
        foreach ($this->userRepository->findAll() as $u) {
            yield $u;
        }
    }

    /**
     * Compatibilité historique: tableau complet.
     * @return UserDTO[]
     */
    public function getAllUsers(): array
    {
        // Matérialisation explicite (dashboard Presenter matérialise de toute façon page-size).
        return \is_array($all = $this->userRepository->findAll())
            ? $all
            : iterator_to_array($all, false);
    }

    public function getUserByUsername(string $username): ?UserDTO
    {
        return $this->userRepository->findByUsername($username);
    }

    public function getUserById(int $id): ?UserDTO
    {
        return $this->userRepository->findById($id);
    }

    public function isAdmin(UserDTO $user): bool
    {
        return $user->role === 'admin';
    }

    /* =======================
       ===== Mutations =======
       ======================= */

    public function createUser(string $username, string $password, string $email, string $role = 'employee'): int
    {
        [$data, $errors] = $this->sanitizeAndValidateCreate($username, $password, $email, $role);
        if ($errors !== []) {
            // On reste sur l'API existante: throw -> le contrôleur catch et PRG
            throw new RuntimeException(reset($errors) ?: 'Invalid payload');
        }

        if ($this->userRepository->existsUsername($data['username'])) {
            throw new RuntimeException('Username already exists');
        }
        if ($this->userRepository->existsEmail($data['email'])) {
            throw new RuntimeException('Email already exists');
        }

        $hash = password_hash($data['password'], PASSWORD_DEFAULT);

        return $this->userRepository->insert([
            'username' => $data['username'],
            'password_hash' => $hash,
            'role' => $data['role'],
            'email' => $data['email'],
        ]);
    }

    public function deleteUser(int $userId): bool
    {
        if ($userId <= 0) {
            throw new RuntimeException('Invalid user id');
        }

        return $this->userRepository->delete($userId) > 0;
    }

    /**
     * @param array<string,mixed> $data
     */
    public function updateUser(int $id, array $data): bool
    {
        if ($id <= 0) {
            throw new RuntimeException('Invalid user id');
        }

        [$clean, $errors] = $this->sanitizeAndValidateUpdate($data);
        if ($errors !== []) {
            throw new RuntimeException(reset($errors) ?: 'Invalid payload');
        }

        return $this->userRepository->update($id, $clean);
    }

    /* =======================
       ===== Helpers =======
       ======================= */

    /** @return array{0: array{username:string,password:string,email:string,role:string}, 1: array<string,string>} */
    private function sanitizeAndValidateCreate(string $username, string $password, string $email, string $role): array
    {
        $u = trim($username);
        $p = $password; // pas de trim mot de passe
        $e = filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : '';
        $r = in_array($role, self::ALLOWED_ROLES, true) ? $role : 'employee';

        $errors = [];
        if ($u === '') {
            $errors['username'] = 'Requis.';
        }
        if ($p === '') {
            $errors['password'] = 'Requis.';
        }
        if ($e === '') {
            $errors['email'] = 'Email invalide.';
        }

        return [[
            'username' => $u,
            'password' => $p,
            'email' => $e,
            'role' => $r,
        ], $errors];
    }

    /** @param array<string,mixed> $data
     *  @return array{0: array<string,mixed>, 1: array<string,string>}
     */
    private function sanitizeAndValidateUpdate(array $data): array
    {
        $u = isset($data['username']) ? trim((string)$data['username']) : '';
        $e = isset($data['email']) ? (string)$data['email'] : '';
        $r = isset($data['role']) ? (string)$data['role'] : 'employee';

        $e = filter_var($e, FILTER_VALIDATE_EMAIL) ? $e : '';

        if (!in_array($r, self::ALLOWED_ROLES, true)) {
            $r = 'employee';
        }

        $errors = [];
        if ($u === '') {
            $errors['username'] = 'Requis.';
        }
        if ($e === '') {
            $errors['email'] = 'Email invalide.';
        }

        return [[
            'username' => $u,
            'email' => $e,
            'role' => $r,
        ], $errors];
    }
}
