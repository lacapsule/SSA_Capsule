<?php

declare(strict_types=1);

namespace App\Controller;

use Capsule\Contracts\ResponseFactoryInterface;
use Capsule\Contracts\ViewRendererInterface;
use Capsule\Domain\Service\UserService;
use Capsule\Routing\Attribute\Route;
use Capsule\Routing\Attribute\RoutePrefix;
use Capsule\Security\CsrfTokenManager;
use Capsule\Auth\CurrentUserProvider;
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

    #[Route(path: '/create', methods: ['POST'])]
    public function create(): Response
    {
        CsrfTokenManager::requireValidToken();

        $username = (string)($_POST['username'] ?? '');
        $password = (string)($_POST['password'] ?? '');
        $email = (string)($_POST['email'] ?? '');
        $role = (string)($_POST['role'] ?? 'employee');

        try {
            $this->userService->createUser($username, $password, $email, $role);

            return $this->redirectWithSuccess('/dashboard/users', 'Utilisateur créé avec succès.');
        } catch (\Throwable) {
            // On reprojette les champs non sensibles pour préfill
            $prefill = [
                'username' => trim($username),
                'email' => filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : '',
                'role' => in_array($role, ['employee','admin'], true) ? $role : 'employee',
            ];

            return $this->redirectWithErrors(
                '/dashboard/users',
                'Le formulaire contient des erreurs.',
                ['_global' => 'Création impossible.'],
                $prefill
            );
        }
    }

    #[Route(path: '/delete', methods: ['POST'])]
    public function delete(): Response
    {
        CsrfTokenManager::requireValidToken();

        $ids = array_map('intval', (array)($_POST['user_ids'] ?? []));
        $ids = array_values(array_filter($ids, static fn (int $id) => $id > 0));

        if ($ids === []) {
            return $this->redirectWithErrors('/dashboard/users', 'Aucun utilisateur sélectionné.', ['_global' => 'Aucun utilisateur sélectionné.']);
        }

        $meId = (int) ((CurrentUserProvider::getUser()['id'] ?? 0));
        $filtered = array_values(array_filter($ids, static fn (int $id) => $id !== $meId));

        if ($filtered === []) {
            return $this->redirectWithErrors('/dashboard/users', 'Aucune suppression effectuée.', ['_global' => 'Vous ne pouvez pas supprimer votre propre compte.']);
        }

        $deleted = 0;
        foreach ($filtered as $id) {
            try {
                if ($this->userService->deleteUser($id)) {
                    $deleted++;
                }
            } catch (\Throwable) { /* partial ok */
            }
        }

        if ($deleted > 0) {
            return $this->redirectWithSuccess('/dashboard/users', "Utilisateur(s) supprimé(s) : {$deleted}.");
        }

        return $this->redirectWithErrors('/dashboard/users', 'Aucune suppression effectuée.', ['_global' => 'Aucune suppression effectuée.']);
    }

    #[Route(path: '/update', methods: ['POST'])]
    public function update(): Response
    {
        CsrfTokenManager::requireValidToken();

        $id = (int)($_POST['id'] ?? 0);
        $username = (string)($_POST['username'] ?? '');
        $email = (string)($_POST['email'] ?? '');
        $role = (string)($_POST['role'] ?? 'employee');

        try {
            $ok = $this->userService->updateUser($id, [
                'username' => $username,
                'email' => $email,
                'role' => $role,
            ]);

            if ($ok) {
                return $this->redirectWithSuccess('/dashboard/users', 'Utilisateur modifié avec succès.');
            }
        } catch (\Throwable) {
            // fallthrough
        }

        return $this->redirectWithErrors(
            '/dashboard/users',
            'Erreur lors de la modification.',
            ['_global' => 'Modification impossible.'],
            ['username' => trim($username), 'email' => (string) filter_var($email, FILTER_VALIDATE_EMAIL) ?: '', 'role' => in_array($role, ['employee','admin'], true) ? $role : 'employee']
        );
    }
}
