<?php

declare(strict_types=1);

namespace App\Controller;

use Capsule\Domain\Service\AuthService;
use App\View\Presenter\AuthPresenter;
use Capsule\Contracts\ResponseFactoryInterface;
use Capsule\Contracts\ViewRendererInterface;
use Capsule\Http\Message\Request;
use Capsule\Http\Message\Response;
use Capsule\Routing\Attribute\Route;
use Capsule\Routing\Attribute\RoutePrefix;
use Capsule\Security\CsrfTokenManager;
use Capsule\View\BaseController;

#[RoutePrefix('')]
final class LoginController extends BaseController
{
    public function __construct(
        private AuthService $auth,                 // ← NEW
        ResponseFactoryInterface $res,
        ViewRendererInterface $view
    ) {
        parent::__construct($res, $view);
    }

    #[Route(path: '/login', methods: ['GET'])]
    public function loginForm(): Response
    {
        $data = AuthPresenter::loginForm(
            i18n: $this->i18n(),
            errors: $this->formErrors(),
            prefill: $this->formData(),
            csrfInput: $this->csrfInput(),
        );

        return $this->page('admin:login', $data);
    }

    #[Route(path: '/login', methods: ['POST'])]
    public function loginSubmit(Request $req): Response
    {
        CsrfTokenManager::requireValidToken();

        $username = (string)($_POST['username'] ?? '');
        $password = (string)($_POST['password'] ?? '');

        $res = $this->auth->login($username, $password);
        if ($res['ok']) {
            return $this->res->redirect('/dashboard/account', 302);
        }

        $msg = $res['error'] === 'missing_fields'
            ? 'Le formulaire contient des erreurs.'
            : 'Identifiants incorrects.';

        return $this->redirectWithErrors(
            '/login',
            $msg,
            ['_global' => $msg],
            ['username' => trim($username)]
        );
    }

    #[Route(path: '/logout', methods: ['GET','POST'])]
    public function logout(): Response
    {
        $this->auth->logout();

        return $this->res->redirect('/login', 302);
    }
}
