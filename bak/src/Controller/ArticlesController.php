<?php

declare(strict_types=1);

namespace App\Controller;

use App\Lang\TranslationLoader;
use App\Navigation\SidebarLinksProvider;
use App\Service\ArticleService;
use CapsuleLib\Core\RenderController;
use CapsuleLib\Http\RequestUtils;
use CapsuleLib\Http\Redirect;
use CapsuleLib\Http\FormState;
use CapsuleLib\Security\CsrfTokenManager;

final class ArticlesController extends RenderController
{
    public function __construct(
        private readonly ArticleService $articles,
        private readonly SidebarLinksProvider $linksProvider
    ) {}

    /* -------------------- Helpers -------------------- */

    private function str(): array
    {
        return TranslationLoader::load(defaultLang: 'fr');
    }

    private function links(bool $isAdmin): array
    {
        return $this->linksProvider->get($isAdmin);
    }

    private function renderDash(string $title, string $component, array $vars = []): void
    {
        // On laisse FlashBag consommé par le layout si tu as déjà un global ;
        // sinon tu peux réactiver un consume() ici.
        echo $this->renderView('dashboard/home.php', [
            'title'            => $title,
            'isDashboard'      => true,
            'isAdmin'          => true,
            'user'             => $_SESSION['admin'] ?? [],
            'links'            => $this->links(true),
            'dashboardContent' => $this->renderComponent($component, $vars + ['str' => $this->str()]),
            'str'              => $this->str(),
            'articleGenerateIcsAction' => '/home/generate_ics',
        ]);
    }

    private function idFrom(string|int|array $param): int
    {
        return RequestUtils::intFromParam($param);
    }

    /* -------------------- Listing -------------------- */

    public function index(): void
    {
        $list = $this->articles->getAll();
        $this->renderDash('Articles', 'dash_articles.php', [
            'articles'      => $list,
            'createUrl'     => '/dashboard/articles/create',
            'editBaseUrl'   => '/dashboard/articles/edit',
            'deleteBaseUrl' => '/dashboard/articles/delete',
            'showBaseUrl'   => '/dashboard/articles/show', // <-- pour le lien "Voir"
        ]);
    }

    /* -------------------- Details -------------------- */

    public function show(string|array $params): void
    {
        $id  = $this->idFrom($params);
        $dto = $this->articles->getById($id);
        if (!$dto) {
            http_response_code(404);
            echo 'Not Found';
            return;
        }

        $this->renderDash('Détail de l’article', 'dash_article_show.php', [
            'article' => $dto,
            'backUrl' => '/dashboard/articles',
        ]);
    }

    /* -------------------- Create -------------------- */

    public function createForm(): void
    {
        $errors = FormState::consumeErrors();
        $data   = FormState::consumeData();

        $this->renderDash('Créer un article', 'dash_article_form.php', [
            'action'  => '/dashboard/articles/create',
            'article' => $data ?? null,
            'errors'  => $errors,
        ]);
    }

    public function createSubmit(): void
    {
        RequestUtils::ensurePostOrRedirect('/dashboard/articles');
        CsrfTokenManager::requireValidToken();

        $result = $this->articles->create($_POST, $_SESSION['admin'] ?? []);
        if (!empty($result['errors'])) {
            Redirect::withErrors(
                '/dashboard/articles/create',
                'Le formulaire contient des erreurs',
                $result['errors'],
                $result['data'] ?? $_POST
            );
        }

        Redirect::withSuccess('/dashboard/articles', 'Article créé.');
    }

    /* -------------------- Edit -------------------- */

    public function editForm(string|array $params): void
    {
        $id  = $this->idFrom($params);
        $dto = $this->articles->getById($id);
        if (!$dto) {
            http_response_code(404);
            echo 'Not Found';
            return;
        }

        $errors  = FormState::consumeErrors();
        $prefill = FormState::consumeData();

        $this->renderDash('Modifier un article', 'dash_article_form.php', [
            'action'  => "/dashboard/articles/edit/{$id}",
            'article' => $prefill ?: $dto,
            'errors'  => $errors,
        ]);
    }

    public function editSubmit(string|array $params): void
    {
        RequestUtils::ensurePostOrRedirect('/dashboard/articles');
        CsrfTokenManager::requireValidToken();

        $id = $this->idFrom($params);
        if (!$this->articles->getById($id)) {
            http_response_code(404);
            echo 'Not Found';
            return;
        }

        $result = $this->articles->update($id, $_POST);
        if (!empty($result['errors'])) {
            Redirect::withErrors(
                "/dashboard/articles/edit/{$id}",
                'Le formulaire contient des erreurs',
                $result['errors'],
                $result['data'] ?? $_POST
            );
        }
        Redirect::withSuccess('/dashboard/articles', 'Article mis à jour.');
    }

    /* -------------------- Delete -------------------- */

    public function deleteSubmit(string|array $params): void
    {
        RequestUtils::ensurePostOrRedirect('/dashboard/articles');
        CsrfTokenManager::requireValidToken();

        $id = $this->idFrom($params);
        $this->articles->delete($id);

        Redirect::withSuccess('/dashboard/articles', 'Article supprimé.');
    }
}
