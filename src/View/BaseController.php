<?php

declare(strict_types=1);

namespace Capsule\View;

use App\Lang\Translate;
use Capsule\Contracts\ViewRendererInterface;
use Capsule\Contracts\ResponseFactoryInterface;
use Capsule\Http\Message\Response;
use Capsule\Security\CsrfTokenManager;
use Capsule\Auth\CurrentUserProvider;
use Capsule\Http\Support\FlashBag;
use Capsule\Http\Support\FormState;

abstract class BaseController
{
    protected string $pageNs = '';
    protected string $componentNs = '';
    protected string $layout = 'main';

    /** @var array<string,string>|null */
    private ?array $i18nCache = null;

    public function __construct(
        protected ResponseFactoryInterface $res,
        protected ViewRendererInterface $view
    ) {
    }

    /** @return array<string,string> */
    protected function i18n(string $default = 'fr'): array
    {
        return $this->i18nCache ??= (Translate::all() + ['lang' => ($_SESSION['lang'] ?? $default)]);
    }

    protected function currentLang(string $default = 'fr'): string
    {
        return $this->i18n($default)['lang'] ?? $default;
    }

    protected function html(string $template, array $data = [], int $status = 200): Response
    {
        $out = $this->view->render($template, $data);

        return $this->res->html($out, $status);
    }

    protected function redirect(string $location, int $status = 302): Response
    {
        return $this->res->redirect($location, $status);
    }

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
        // 1. Résolution du nom logique de la page
        $logical = str_contains($name, ':')
            ? $name
            : ($this->pageNs !== '' ? "page:{$this->pageNs}/{$name}" : "page:{$name}");

        // 2. ✅ Rendu du contenu de la page (SANS layout) - utiliser render() directement
        $content = $this->view->render($logical, $data);

        // 3. Injection du contenu dans le layout
        $layoutLogical = "layout:{$this->layout}";
        $data['content'] = $content;

        $out = $this->view->render($layoutLogical, $data);

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

    protected function setLayout(string $layout): void
    {
        $this->layout = $layout;
    }

    protected function csrfInput(): string
    {
        return CsrfTokenManager::insertInput();
    }

    /**
     * @return array{id?:int,username?:string,role?:string,email?:string}
     */
    protected function currentUser(): array
    {
        return CurrentUserProvider::getUser() ?? [];
    }

    protected function isAuthenticated(): bool
    {
        return CurrentUserProvider::isAuthenticated();
    }

    protected function isAdmin(): bool
    {
        $user = $this->currentUser();

        return ($user['role'] ?? null) === 'admin';
    }

    /**
     * @param array<string,string> $errors
     * @param array<string,mixed>  $data
     */
    protected function redirectWithErrors(string $to, string $flash, array $errors, array $data = []): Response
    {
        FormState::set($errors, $data);
        FlashBag::add('error', $flash);

        return $this->res->redirect($to, 303);
    }

    protected function redirectWithSuccess(string $to, string $flash): Response
    {
        FlashBag::add('success', $flash);

        return $this->res->redirect($to, 303);
    }

    /**
     * @return array<string,array<mixed>>
     */
    protected function flashMessages(): array
    {
        return FlashBag::consume();
    }

    /**
     * @return array<string,string>
     */
    protected function formErrors(): array
    {
        return FormState::consumeErrors() ?? [];
    }

    /**
     * @return array<string,mixed>
     */
    protected function formData(): array
    {
        return FormState::consumeData() ?? [];
    }
}
