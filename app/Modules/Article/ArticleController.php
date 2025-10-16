<?php

declare(strict_types=1);

namespace App\Modules\Article;

use App\Providers\SidebarLinksProvider;
use Capsule\Contracts\ResponseFactoryInterface;
use Capsule\Contracts\ViewRendererInterface;
use Capsule\Http\Message\Response;
use Capsule\Routing\Attribute\Route;
use Capsule\Routing\Attribute\RoutePrefix;
use Capsule\Security\CsrfTokenManager;
use Capsule\Support\Pagination\Paginator;
use Capsule\View\BaseController;

#TODO: Add logic mettre image (en go)
#[RoutePrefix('/dashboard/articles')]
final class ArticleController extends BaseController
{
    //  Configuration du module Article (dans le contexte Dashboard)
    protected string $pageNs = 'dashboard';
    protected string $componentNs = 'dashboard';
    protected string $layout = 'dashboard';  // Layout dashboard avec sidebar

    public function __construct(
        private readonly ArticleService $articles,
        private readonly SidebarLinksProvider $linksProvider,
        ResponseFactoryInterface $res,
        ViewRendererInterface $view,
    ) {
        parent::__construct($res, $view);
    }

    /**
     * Shell commun dashboard
     * @return array<string,mixed>
     */
    private function base(): array
    {
        return [
            'str' => $this->i18n(),
            'user' => $this->currentUser(),
            'isAdmin' => $this->isAdmin(),
            'links' => $this->linksProvider->get($this->isAdmin()),
            'flash' => $this->flashMessages(),
        ];
    }

    /** GET /dashboard/articles */
    #[Route(path: '', methods: ['GET'])]
    public function index(): Response
    {
        $base = $this->base();
        $page = Paginator::fromGlobals(defaultLimit: 20, maxLimit: 200);

        // Domaine: flux lazy de tous les articles (tri DESC par repo)
        $iter = $this->articles->getAll(); // iterable<ArticleDTO>

        // Présentation: projection + matérialisation page-size
        $data = ArticlePresenter::list(
            base: $base,
            articles: $iter,
            page: $page->page,
            limit: $page->limit,
            csrfInput: $this->csrfInput(),
        );

        // Composant dynamique
        $data['component'] = 'dashboard/components/articles';

        // Résout vers page:dashboard/index avec component:dashboard/components/articles
        return $this->page('index', $data + ['title' => 'Gestion des articles']);
    }

    /** GET /dashboard/articles/show/{id} */
    #[Route(path: '/show/{id}', methods: ['GET'])]
    public function show(int $id): Response
    {
        $dto = $this->articles->getById($id);
        if (!$dto) {
            return $this->res->text('Not Found', 404);
        }

        $data = ArticlePresenter::show($this->base(), $dto);
        $data['component'] = 'dashboard/components/article_show';

        return $this->page('index', $data + ['title' => 'Détails de l\'article']);
    }

    /** GET /dashboard/articles/create */
    #[Route(path: '/create', methods: ['GET'])]
    public function createForm(): Response
    {
        $data = ArticlePresenter::form(
            base: $this->base(),
            title: 'Créer un article',
            action: '/dashboard/articles/create',
            src: $this->formData() ?: null,
            errors: $this->formErrors(),
            csrfInput: $this->csrfInput(),
        );

        $data['component'] = 'dashboard/components/article_form';

        return $this->page('index', $data + ['title' => 'Créer un article']);
    }

    /** POST /dashboard/articles/create */
    #[Route(path: '/create', methods: ['POST'])]
    public function createSubmit(): Response
    {
        CsrfTokenManager::requireValidToken();

        $current = $this->currentUser();
        $result = $this->articles->create($_POST, $current);

        if (!empty($result['errors'])) {
            return $this->redirectWithErrors(
                '/dashboard/articles/create',
                'Le formulaire contient des erreurs.',
                $result['errors'],
                $result['data'] ?? $_POST
            );
        }

        return $this->redirectWithSuccess('/dashboard/articles', 'Article créé.');
    }

    /** GET /dashboard/articles/edit/{id} */
    #[Route(path: '/edit/{id}', methods: ['GET'])]
    public function editForm(int $id): Response
    {
        $dto = $this->articles->getById($id);
        if (!$dto) {
            return $this->res->text('Not Found', 404);
        }

        $prefill = $this->formData() ?: $dto;

        $data = ArticlePresenter::form(
            base: $this->base(),
            title: 'Modifier un article',
            action: "/dashboard/articles/edit/{$id}",
            src: $prefill,
            errors: $this->formErrors(),
            csrfInput: $this->csrfInput(),
        );

        $data['component'] = 'dashboard/components/article_form';

        return $this->page('index', $data + ['title' => 'Modifier l\'article']);
    }

    /** POST /dashboard/articles/edit/{id} */
    #[Route(path: '/edit/{id}', methods: ['POST'])]
    public function editSubmit(int $id): Response
    {
        CsrfTokenManager::requireValidToken();

        if (!$this->articles->getById($id)) {
            return $this->res->text('Not Found', 404);
        }

        $result = $this->articles->update($id, $_POST);
        if (!empty($result['errors'])) {
            return $this->redirectWithErrors(
                "/dashboard/articles/edit/{$id}",
                'Le formulaire contient des erreurs.',
                $result['errors'],
                $result['data'] ?? $_POST
            );
        }

        return $this->redirectWithSuccess('/dashboard/articles', 'Article mis à jour.');
    }

    /** POST /dashboard/articles/delete/{id} */
    #[Route(path: '/delete/{id}', methods: ['POST'])]
    public function deleteSubmit(int $id): Response
    {
        CsrfTokenManager::requireValidToken();

        // idempotent : delete "silencieux"
        $this->articles->delete($id);

        return $this->redirectWithSuccess('/dashboard/articles', 'Article supprimé.');
    }
}
