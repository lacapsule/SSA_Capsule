<?php

declare(strict_types=1);

namespace Capsule\Domain\Service;

use Capsule\Domain\Repository\UserRepository;
use Capsule\Http\Support\SessionManager;

final class AuthService
{
    public function __construct(
        private UserRepository $users
    ) {
    }

    /**
     * @return array{ok: bool, error?: string}
     */
    public function login(string $username, string $password): array
    {
        if (trim($username) === '' || $password === '') {
            return ['ok' => false, 'error' => 'missing_fields'];
        }

        $userDto = $this->users->findByUsername($username);

        if (!$userDto) {
            return ['ok' => false, 'error' => 'invalid_credentials'];
        }

        if (!password_verify($password, $userDto->passwordHash)) {
            return ['ok' => false, 'error' => 'invalid_credentials'];
        }

        SessionManager::regenerateId();

        // ✅ Utilise la méthode helper du DTO
        SessionManager::set('admin', $userDto->toSessionArray());

        SessionManager::commit();

        return ['ok' => true];
    }

    public function logout(): void
    {
        SessionManager::destroy();
    }

    public function isAuthenticated(): bool
    {
        return SessionManager::has('admin');
    }

    /**
     * @return array{id:int,username:string,role:string,email:string}|null
     */
    public function getCurrentUser(): ?array
    {
        return SessionManager::get('admin');
    }
}
