<?php

declare(strict_types=1);

namespace App\Modules\Login;

use Capsule\Domain\Service\AuthService;
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
    protected string $pageNs = 'login';
    protected string $componentNs = 'login';
    protected string $layout = 'main';  // Layout public (ou créer un layout minimal)

    public function __construct(
        private AuthService $auth,
        ResponseFactoryInterface $res,
        ViewRendererInterface $view
    ) {
        parent::__construct($res, $view);
    }

    /**
     * GET /login
     * Affiche le formulaire de connexion
     */
    #[Route(path: '/login', methods: ['GET'])]
    public function loginForm(): Response
    {
        // Si déjà connecté, rediriger vers le dashboard
        if ($this->isAuthenticated()) {
            return $this->redirect('/dashboard');
        }

        $data = LoginPresenter::loginForm(
            i18n: $this->i18n(),
            errors: $this->formErrors(),
            prefill: $this->formData(),
            csrfInput: $this->csrfInput(),
        );

        // Résout vers page:login/login avec layout:main
        return $this->page('login', $data + [
            'title' => 'Connexion',
            'showHeader' => false,  // Pas de header sur la page de login
            'showFooter' => false,  // Pas de footer sur la page de login
        ]);
    }

    /**
     * POST /login
     * Traite la soumission du formulaire de connexion
     */
    #[Route(path: '/login', methods: ['POST'])]
    public function loginSubmit(Request $req): Response
    {
        CsrfTokenManager::requireValidToken();

        $username = trim((string)($_POST['username'] ?? ''));
        $password = (string)($_POST['password'] ?? '');

        $res = $this->auth->login($username, $password);

        if ($res['ok']) {
            // Connexion réussie → redirection vers le dashboard
            return $this->redirectWithSuccess(
                '/dashboard',
                'Connexion réussie. Bienvenue !'
            );
        }

        // Connexion échouée
        $msg = $res['error'] === 'missing_fields'
            ? 'Veuillez remplir tous les champs.'
            : 'Identifiants incorrects.';

        return $this->redirectWithErrors(
            '/login',
            $msg,
            ['_global' => $msg],
            ['username' => $username]  // Pré-remplissage du username uniquement
        );
    }

    /**
     * GET|POST /logout
     * Déconnecte l'utilisateur
     */
    #[Route(path: '/logout', methods: ['GET', 'POST'])]
    public function logout(): Response
    {
        $this->auth->logout();

        return $this->redirectWithSuccess(
            '/',
            'Vous avez été déconnecté avec succès.'
        );
    }
}
