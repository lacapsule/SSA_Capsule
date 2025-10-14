<?php

declare(strict_types=1);

namespace App\Controller;

use App\Provider\SidebarLinksProvider;
use App\Service\DashboardService;
use App\View\Presenter\DashboardPresenter;
use Capsule\Contracts\ResponseFactoryInterface;
use Capsule\Contracts\ViewRendererInterface;
use Capsule\Domain\Service\PasswordService;
use Capsule\Http\Message\Response;
use Capsule\Routing\Attribute\Route;
use Capsule\Routing\Attribute\RoutePrefix;
use Capsule\Security\CsrfTokenManager;
use Capsule\Support\Pagination\Paginator;
use Capsule\View\BaseController;

#[RoutePrefix('/dashboard')]
final class DashboardController extends BaseController
{
    public function __construct(
        private readonly DashboardService $dashboard,     // NEW
        private readonly PasswordService $passwords,
        private readonly SidebarLinksProvider $links,
        ResponseFactoryInterface $res,
        ViewRendererInterface $view,
    ) {
        parent::__construct($res, $view);
    }

    /** Fabrique le “shell” du dashboard (pas de vue ici) */
    private function baseShell(): array
    {
        $i18n = $this->i18n();
        $user = $this->currentUser();
        $isAdmin = $this->isAdmin();
        $links = $this->links->get($isAdmin);
        $flash = $this->flashMessages();

        return DashboardPresenter::base($i18n, $user, $isAdmin, $links, $flash);
    }

    /* ---------------- Routes (GET) ---------------- */

    #[Route(path: '', methods: ['GET'])]
    public function index(): Response
    {
        $base = $this->baseShell();

        return $this->page('dashboard:home', $base + [
            'title' => 'Dashboard',
            // si tu as un composant par défaut :
            // 'component' => 'dashboard/dash_overview',
        ]);
    }

    #[Route(path: '/users', methods: ['GET'])]
    public function users(): Response
    {
        $base = $this->baseShell();
        $page = Paginator::fromGlobals(defaultLimit: 20, maxLimit: 200);
        $errors = $this->formErrors();
        $prefill = $this->formData();

        // Domaine
        $usersIt = $this->dashboard->getUsers($page); // iterable<object>

        // Vue (Presenter) — matérialise page-size
        $data = DashboardPresenter::users(
            base: $base,
            users: $usersIt,
            errors: $errors,
            prefill: $prefill,
            csrfInput: $this->csrfInput()
        );

        return $this->page('dashboard:home', $data);
    }

    #[Route(path: '/account', methods: ['GET'])]
    public function account(): Response
    {
        $base = $this->baseShell();
        $errors = $this->formErrors();
        $prefill = $this->formData();

        $data = DashboardPresenter::account(
            base: $base,
            errors: $errors,
            prefill: $prefill,
            csrfInput: $this->csrfInput(),
        );

        return $this->page('dashboard:home', $data);
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
