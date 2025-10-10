<?php

declare(strict_types=1);

namespace Capsule\View;

use Capsule\Contracts\ViewRendererInterface;
use Capsule\Contracts\ResponseFactoryInterface;
use Capsule\Http\Message\Response;
use Capsule\Security\CsrfTokenManager;
use Capsule\Security\CurrentUserProvider;
use Capsule\Http\Support\FlashBag;
use Capsule\Http\Support\FormState;

/**
 * Contrôleur de Base
 *
 * - Orchestration HTTP (rendu, redirections) via ResponseFactoryInterface.
 * - Aides PRG (withErrors/withSuccess) : stockent l’état puis renvoient une 303.
 * - Helpers d’accès à l’utilisateur courant et aux messages flash.
 *
 * Invariants :
 * - formErrors()/formData() retournent toujours un array (jamais null).
 * - page()/comp() résolvent les noms logiques avec pageNs/componentNs si fournis.
 */
abstract class BaseController
{
    use TranslationTrait;

    /**
     * Namespace par défaut pour les pages (surchargeable)
     * Ex: 'dashboard' pour les pages du dashboard.
     */
    protected string $pageNs = '';

    /**
     * Namespace par défaut pour les composants (surchargeable)
     * Ex: 'dashboard' pour les composants du dashboard.
     */
    protected string $componentNs = '';

    public function __construct(
        protected ResponseFactoryInterface $res,
        protected ViewRendererInterface $view
    ) {
    }

    /**
     * Rend une template avec layout (héritage historique).
     * @deprecated Utiliser page() à la place.
     *
     * @param array<string,mixed> $data
     */
    protected function html(string $template, array $data = [], int $status = 200): Response
    {
        $out = $this->view->render($template, $data);

        return $this->res->html($out, $status);
    }

    /**
     * Redirection HTTP standard.
     */
    protected function redirect(string $location, int $status = 302): Response
    {
        return $this->res->redirect($location, $status);
    }

    /**
     * Rend un composant sans layout (héritage historique).
     * @deprecated Utiliser comp() à la place.
     *
     * @param array<string,mixed> $data
     */
    protected function component(string $componentPath, array $data = []): string
    {
        return $this->view->renderComponent($componentPath, $data);
    }

    /**
     * Rend une page avec layout via noms logiques.
     *
     * @param array<string,mixed> $data
     */
    protected function page(string $name, array $data = [], int $status = 200): Response
    {
        $logical = str_contains($name, ':')
            ? $name
            : ($this->pageNs !== '' ? "page:{$this->pageNs}/{$name}" : "page:{$name}");

        $out = $this->view->render($logical, $data);

        return $this->res->html($out, $status);
    }

    /**
     * Rend un composant (fragment) via noms logiques.
     *
     * @param array<string,mixed> $data
     */
    protected function comp(string $name, array $data = []): string
    {
        $logical = str_contains($name, ':')
            ? $name
            : ($this->componentNs !== '' ? "component:{$this->componentNs}/{$name}" : "component:{$name}");

        return $this->view->renderComponent($logical, $data);
    }

    /**
     * Champ CSRF (HTML <input ...>) prêt à insérer dans un formulaire.
     */
    protected function csrfInput(): string
    {
        return CsrfTokenManager::insertInput();
    }

    /**
     * Données utilisateur courant.
     * @return array{id?:int,username?:string,role?:string,email?:string}
     */
    protected function currentUser(): array
    {
        return CurrentUserProvider::getUser() ?? [];
    }

    /**
     * Indique si un utilisateur est authentifié.
     */
    protected function isAuthenticated(): bool
    {
        return CurrentUserProvider::isAuthenticated();
    }

    /**
     * Vérifie si l’utilisateur courant est admin.
     */
    protected function isAdmin(): bool
    {
        $user = $this->currentUser();

        return ($user['role'] ?? null) === 'admin';
    }

    /**
     * Redirection PRG avec erreurs + pré-remplissage.
     *
     * @param array<string,string> $errors
     * @param array<string,mixed>  $data
     */
    protected function redirectWithErrors(string $to, string $flash, array $errors, array $data = []): Response
    {
        FormState::set($errors, $data);
        FlashBag::add('error', $flash);

        return $this->res->redirect($to, 303);
    }

    /**
     * Redirection PRG avec succès.
     */
    protected function redirectWithSuccess(string $to, string $flash): Response
    {
        FlashBag::add('success', $flash);

        return $this->res->redirect($to, 303);
    }

    /**
     * Consomme les messages flash (one-shot).
     * @return array<string,array<mixed>>
     */
    protected function flashMessages(): array
    {
        return FlashBag::consume();
    }

    /**
     * Consomme les erreurs de formulaire (one-shot).
     * @return array<string,string>
     */
    protected function formErrors(): array
    {
        return FormState::consumeErrors() ?? [];
    }

    /**
     * Consomme les données de formulaire (one-shot).
     * @return array<string,mixed>
     */
    protected function formData(): array
    {
        return FormState::consumeData() ?? [];
    }
}
