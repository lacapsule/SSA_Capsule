<?php

declare(strict_types=1);

namespace App\Controller;

use CapsuleLib\Core\RenderController;
use CapsuleLib\Security\Authenticator;
use CapsuleLib\Security\CsrfTokenManager;
use App\Lang\TranslationLoader;
use CapsuleLib\Http\RequestUtils;
use CapsuleLib\Http\Redirect;
use CapsuleLib\Http\FlashBag;
use CapsuleLib\Http\FormState;
use PDO;

/**
 * Contrôleur pour la gestion de l'administration (authentification, tableau de bord).
 *
 * Gère le formulaire de connexion, la soumission du login,
 * l'affichage du dashboard et la déconnexion.
 *
 * Applique la vérification CSRF et la gestion des sessions.
 */
class LoginController extends RenderController
{
    /**
     * Instance PDO pour les opérations liées à la base de données.
     */
    private PDO $pdo;

    /**
     * Constructeur.
     *
     * @param PDO $pdo Instance PDO pour accès base de données.
     */
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Charge les chaînes de traduction pour la page courante.
     *
     * @return array<string, string> Tableau associatif des traductions.
     */
    private function getStrings(): array
    {
        return TranslationLoader::load(defaultLang: 'fr');
    }

    /**
     * Affiche le formulaire de connexion.
     *
     * @return void
     */
    public function loginForm(): void
    {
        $errors  = FormState::consumeErrors();
        $prefill = FormState::consumeData();
        echo $this->renderView('admin/login.php', [
            'showHeader' => true,
            'showFooter' => true,
            'title' => 'Connexion',
            'error' => $errors['_global'] ?? null,
            'errors' => $errors,
            'prefill' => $prefill,
            'str'   => $this->getStrings(),
        ]);
    }

    /**
     * Traite la soumission du formulaire de connexion.
     */
    public function loginSubmit(): void
    {
        RequestUtils::ensurePostOrRedirect('/login');
        CsrfTokenManager::requireValidToken();

        $username = trim((string)($_POST['username'] ?? ''));
        $password = (string)($_POST['password'] ?? '');

        if ($username === '' || $password === '') {
            FormState::set(['_global' => 'Champs requis manquants.'], ['username' => $username]);
            FlashBag::add('error', 'Le formulaire contient des erreurs.');
            Redirect::to('/login');
        }

        $success = Authenticator::login(
            $this->pdo,
            $username,
            $password
        );

        if ($success) {
            Redirect::to('/dashboard/home', 302);
        }

        // PRG en cas d'échec d’authentification
        FormState::set(['_global' => 'Identifiants incorrects.'], ['username' => $username]);
        FlashBag::add('error', 'Identifiants incorrects.');
        Redirect::to('/login');
    }

    public function logout(): void
    {
        Authenticator::logout();
        Redirect::to('/login');
    }
}
