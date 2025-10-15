<?php

declare(strict_types=1);

namespace Capsule\Domain\Service;

use Capsule\Domain\Repository\UserRepository;

/**
 * Orchestration d’authentification :
 * - lookup user
 * - verify password
 * - ouvrir session via SessionAuth
 *
 * Invariants:
 * - Ne jette pas le password dans la vue.
 * - Ne divulgue pas si username existe (même message d’erreur).
 */
final class AuthService
{
    public function __construct(
        private UserRepository $users,
        private SessionAuthService $session // wrapper sur tes opérations de session
    ) {
    }

    /** @return array{ok:bool, error?:string} */
    public function login(string $username, string $password): array
    {
        $username = trim($username);
        if ($username === '' || $password === '') {
            return ['ok' => false, 'error' => 'missing_fields'];
        }

        $user = $this->users->findByUsername($username);
        if (!$user) {
            // timing: calcule un hash pour homogénéiser le temps
            password_verify($password, password_hash('x', PASSWORD_DEFAULT));

            return ['ok' => false, 'error' => 'bad_credentials'];
        }

        if (!password_verify($password, $user->password_hash)) {
            return ['ok' => false, 'error' => 'bad_credentials'];
        }

        $this->session->regenerateId();
        $this->session->setUser([
            'id' => $user->id,
            'username' => $user->username,
            'role' => $user->role,
            'email' => $user->email,
        ]);
        $this->session->commit();

        return ['ok' => true];
    }

    public function logout(): void
    {
        $this->session->destroy();
    }
}
