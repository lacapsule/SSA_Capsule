<?php

declare(strict_types=1);

namespace App\Controller;

use Capsule\Contracts\ResponseFactoryInterface;
use Capsule\Contracts\ViewRendererInterface;
use Capsule\Http\Message\Request;
use Capsule\Http\Message\Response;
use Capsule\Routing\Attribute\Route;
use Capsule\Routing\Attribute\RoutePrefix;
use Capsule\Security\Authenticator;
use Capsule\Security\CsrfTokenManager;
use Capsule\View\BaseController;
use PDO;

#[RoutePrefix('')]
final class LoginController extends BaseController
{
    public function __construct(
        private PDO $pdo,
        ResponseFactoryInterface $res,
        ViewRendererInterface $view
    ) {
        parent::__construct($res, $view);
    }

    /** GET /login — affiche le formulaire */
    #[Route(path: '/login', methods: ['GET'])]
    public function loginForm(): Response
    {
        $errors = $this->formErrors();   // toujours array
        $prefill = $this->formData();     // toujours array

        // Résout en "page:admin/login" via $this->pageNs
        return $this->page('admin:login', [
             'showHeader' => true,
             'showFooter' => true,
             'title' => 'Connexion',
             'str' => $this->translations(),
             'error' => $errors['_global'] ?? null,
             'errors' => $errors,
             'prefill' => $prefill,
             'csrf_input' => $this->csrfInput(), // {{{csrf_input}}}
             'action' => '/login',
         ]);
    }


    /** POST /login — traite la soumission */
    #[Route(path: '/login', methods: ['POST'])]
    public function loginSubmit(Request $req): Response
    {
        CsrfTokenManager::requireValidToken();

        $username = trim((string)($_POST['username'] ?? ''));
        $password = (string)($_POST['password'] ?? '');

        if ($username === '' || $password === '') {
            // PRG standardisé (stocke errors+data+flash et redirige)
            return $this->redirectWithErrors(
                '/login',
                'Le formulaire contient des erreurs.',
                ['_global' => 'Champs requis manquants.'],
                ['username' => $username] // jamais le password
            );
        }

        $success = Authenticator::login($this->pdo, $username, $password);

        if ($success) {
            return $this->res->redirect('/dashboard/account', 302);
        }

        return $this->redirectWithErrors(
            '/login',
            'Identifiants incorrects.',
            ['_global' => 'Identifiants incorrects.'],
            ['username' => $username]
        );
    }

    /** GET/POST /logout — détruit la session et redirige */
    #[Route(path: '/logout', methods: ['GET', 'POST'])]
    public function logout(): Response
    {
        Authenticator::logout();

        return $this->res->redirect('/login', 302);
    }
}
