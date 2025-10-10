<?php

declare(strict_types=1);

namespace App\Controller;

use App\Navigation\SidebarLinksProvider;
use Capsule\Contracts\ResponseFactoryInterface;
use Capsule\Contracts\ViewRendererInterface;
use Capsule\Domain\Service\PasswordService;
use Capsule\Domain\Service\UserService;
use Capsule\Http\Message\Response;
use Capsule\Routing\Attribute\Route;
use Capsule\Routing\Attribute\RoutePrefix;
use Capsule\Security\CsrfTokenManager;
use Capsule\View\BaseController;

#[RoutePrefix('/dashboard')]
final class DashboardController extends BaseController
{
    public function __construct(
        private readonly UserService $users,
        private readonly PasswordService $passwords,
        private readonly SidebarLinksProvider $links,
        ResponseFactoryInterface $res,
        ViewRendererInterface $view,
    ) {
        parent::__construct($res, $view);
    }

    /** @return list<array{title:string,url:string,icon:string}> */
    private function sidebar(bool $isAdmin): array
    {
        return $this->links->get($isAdmin);
    }

    /** payload commun au shell dashboard
     *  @param array<string,mixed> $extra
     *  @return array<string,mixed>
     */
    private function base(array $extra = []): array
    {
        $user = $this->currentUser();
        $isAdmin = $this->isAdmin();

        $base = [
            'showHeader' => false,
            'showFooter' => false,
            'isDashboard' => true,
            'str' => $this->translations(),
            'user' => $user,
            'isAdmin' => $isAdmin,
            'links' => $this->sidebar($isAdmin),
            'flash' => $this->flashMessages(),
        ];

        return array_replace($base, $extra);
    }



    /* ---------------- Routes (GET) ---------------- */

    #[Route(path: '', methods: ['GET'])]
    public function index(): Response
    {
        return $this->page('dashboard:home', $this->base([
            'title' => 'Dashboard',
        ]));
    }

    #[Route(path: '/users', methods: ['GET'])]
    public function users(): Response
    {
        $errors = $this->formErrors();
        $prefill = $this->formData();

        return $this->page('dashboard:home', $this->base([
            'title' => 'Utilisateurs',
            'component' => 'dashboard/dash_users',
            'users' => $this->users->getAllUsers(),
            'errors' => $errors,
            'prefill' => $prefill,
            'createAction' => '/dashboard/users/create',
            'deleteAction' => '/dashboard/users/delete',
            'csrf_input' => $this->csrfInput(),
        ]));
    }

    #[Route(path: '/account', methods: ['GET'])]
    public function account(): Response
    {
        $errors = $this->formErrors();
        $prefill = $this->formData();

        return $this->page('dashboard:home', $this->base([
            'title' => 'Mon compte',
            'component' => 'dashboard/dash_account',
            'errors' => $errors,
            'prefill' => $prefill,
            'action' => '/dashboard/account/password',
            'editUserAction' => '/dashboard/account/update',
            'csrf_input' => $this->csrfInput(),
        ]));
    }

    #[Route(path: '/agenda', methods: ['GET'])]
    public function agenda(): Response
    {
        return $this->page('dashboard:home', $this->base([
            'title' => 'Mon agenda',
            'component' => 'dashboard/dash_agenda',
        ]));
    }
    /* ---------------- Actions (POST) ---------------- */

    #[Route(path: '/account/password', methods: ['POST'])]
    public function accountPassword(): Response
    {
        CsrfTokenManager::requireValidToken();

        $userId = (int) (($this->currentUser()['id'] ?? 0));
        if ($userId <= 0) {
            return $this->res->text('Forbidden', 403);
        }

        $old = trim((string)($_POST['old_password'] ?? ''));
        $new = trim((string)($_POST['new_password'] ?? ''));
        $confirm = trim((string)($_POST['confirm_new_password'] ?? ''));

        $errors = [];
        if ($new === '' || $old === '') {
            $errors['_global'] = 'Champs requis manquants.';
        } elseif ($new !== $confirm) {
            $errors['confirm_new_password'] = 'Les nouveaux mots de passe ne correspondent pas.';
        }

        if ($errors === []) {
            [$ok, $svcErrors] = $this->passwords->changePassword($userId, $old, $new);
            if ($ok) {
                return $this->redirectWithSuccess(
                    '/dashboard/account',
                    'Mot de passe modifié avec succès.'
                );
            }
            $errors = $svcErrors ?: ['_global' => 'Échec de la modification du mot de passe.'];
        }

        return $this->redirectWithErrors(
            '/dashboard/account',
            'Le formulaire contient des erreurs.',
            $errors,
            [] // pas de pré-remplissage sensible ici
        );
    }
}
