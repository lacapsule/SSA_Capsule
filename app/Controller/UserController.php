<?php

declare(strict_types=1);

namespace App\Controller;

use Capsule\Contracts\ResponseFactoryInterface;
use Capsule\Contracts\ViewRendererInterface;
use Capsule\Domain\Service\UserService;
use Capsule\Routing\Attribute\Route;
use Capsule\Routing\Attribute\RoutePrefix;
use Capsule\Security\CsrfTokenManager;
use Capsule\Security\CurrentUserProvider;
use Capsule\View\BaseController;
use Capsule\Http\Message\Response;

#[RoutePrefix('/dashboard/users')]
final class UserController extends BaseController
{
    public function __construct(
        private readonly UserService $userService,
        ResponseFactoryInterface $res,
        ViewRendererInterface $view,
    ) {
        parent::__construct($res, $view);
    }

    /* =========================================================
     * POST /dashboard/users/create
     * =======================================================*/
    #[Route(path: '/create', methods: ['POST'])]
    public function create(): Response
    {
        CsrfTokenManager::requireValidToken();

        $username = trim((string)($_POST['username'] ?? ''));
        $password = (string)($_POST['password'] ?? '');
        $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL) ?: null;
        $role = trim((string)($_POST['role'] ?? 'employee'));

        // Rôles autorisés
        $allowedRoles = ['employee', 'admin'];
        if (!in_array($role, $allowedRoles, true)) {
            $role = 'employee';
        }

        $errors = [];
        if ($username === '') {
            $errors['username'] = 'Requis.';
        }
        if ($password === '') {
            $errors['password'] = 'Requis.';
        }
        if (!$email) {
            $errors['email'] = 'Email invalide.';
        }

        if ($errors !== []) {
            return $this->redirectWithErrors(
                '/dashboard/users',
                'Le formulaire contient des erreurs.',
                $errors,
                ['username' => $username, 'email' => (string)$email, 'role' => $role]
            );
        }

        try {
            $this->userService->createUser($username, $password, (string)$email, $role);

            return $this->redirectWithSuccess('/dashboard/users', 'Utilisateur créé avec succès.');
        } catch (\Throwable $e) {
            return $this->redirectWithErrors(
                '/dashboard/users',
                'Erreur lors de la création.',
                ['_global' => 'Création impossible.'],
                ['username' => $username, 'email' => (string)$email, 'role' => $role]
            );
        }
    }

    /* =========================================================
     * POST /dashboard/users/delete
     * =======================================================*/
    #[Route(path: '/delete', methods: ['POST'])]
    public function delete(): Response
    {
        CsrfTokenManager::requireValidToken();

        $ids = array_map('intval', (array)($_POST['user_ids'] ?? []));
        $ids = array_values(array_filter($ids, static fn (int $id) => $id > 0));

        if ($ids === []) {
            return $this->redirectWithErrors(
                '/dashboard/users',
                'Aucun utilisateur sélectionné.',
                ['_global' => 'Aucun utilisateur sélectionné.']
            );
        }

        // Option: éviter de supprimer son propre compte
        $meId = (int) ((CurrentUserProvider::getUser()['id'] ?? 0));
        $filtered = array_values(array_filter($ids, static fn (int $id) => $id !== $meId));

        if ($filtered === []) {
            return $this->redirectWithErrors(
                '/dashboard/users',
                'Aucune suppression effectuée.',
                ['_global' => 'Vous ne pouvez pas supprimer votre propre compte.']
            );
        }

        $deleted = 0;
        foreach ($filtered as $id) {
            try {
                $this->userService->deleteUser($id);
                $deleted++;
            } catch (\Throwable) {
                // on continue, on pourra compter partiellement
            }
        }

        if ($deleted > 0) {
            return $this->redirectWithSuccess('/dashboard/users', "Utilisateur(s) supprimé(s) : {$deleted}.");
        }

        return $this->redirectWithErrors(
            '/dashboard/users',
            'Aucune suppression effectuée.',
            ['_global' => 'Aucune suppression effectuée.']
        );
    }

    /* =========================================================
     * POST /dashboard/users/update
     * =======================================================*/
    #[Route(path: '/update', methods: ['POST'])]
    public function update(): Response
    {
        CsrfTokenManager::requireValidToken();

        $id = (int)($_POST['id'] ?? 0);
        $username = trim((string)($_POST['username'] ?? ''));
        $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL) ?: null;
        $role = trim((string)($_POST['role'] ?? 'employee'));

        $allowedRoles = ['employee', 'admin'];
        if (!in_array($role, $allowedRoles, true)) {
            $role = 'employee';
        }

        $errors = [];
        if ($id <= 0) {
            $errors['_global'] = 'ID utilisateur invalide.';
        }
        if ($username === '') {
            $errors['username'] = 'Requis.';
        }
        if (!$email) {
            $errors['email'] = 'Email invalide.';
        }

        if ($errors !== []) {
            return $this->redirectWithErrors(
                '/dashboard/users',
                'Le formulaire contient des erreurs.',
                $errors,
                ['username' => $username, 'email' => (string)$email, 'role' => $role]
            );
        }

        try {
            $this->userService->updateUser($id, [
                'username' => $username,
                'email' => (string)$email,
                'role' => $role,
            ]);

            return $this->redirectWithSuccess('/dashboard/users', 'Utilisateur modifié avec succès.');
        } catch (\Throwable $e) {
            return $this->redirectWithErrors(
                '/dashboard/users',
                'Erreur lors de la modification.',
                ['_global' => 'Modification impossible.'],
                ['username' => $username, 'email' => (string)$email, 'role' => $role]
            );
        }
    }
}
