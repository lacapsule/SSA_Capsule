<?php

declare(strict_types=1);

namespace App\Controller;

use App\Navigation\SidebarLinksProvider;
use App\Service\ArticleService;
use Capsule\Contracts\ResponseFactoryInterface;
use Capsule\Contracts\ViewRendererInterface;
use Capsule\Http\Message\Response;
use Capsule\Routing\Attribute\Route;
use Capsule\Routing\Attribute\RoutePrefix;
use Capsule\Security\CsrfTokenManager;
use Capsule\View\BaseController;

#[RoutePrefix('/dashboard/articles')]
final class ArticlesController extends BaseController
{
    public function __construct(
        private readonly ArticleService $articles,
        private readonly SidebarLinksProvider $linksProvider,
        ResponseFactoryInterface $res,
        ViewRendererInterface $view,
    ) {
        parent::__construct($res, $view);
    }

    /* -------------------------------------------------------
     * Helpers
     * ----------------------------------------------------- */

    /** @return list<array{title:string,url:string,icon:string}> */
    private function sidebarLinks(): array
    {
        return $this->linksProvider->get($this->isAdmin());
    }

    /**
     * @param array<string,mixed> $extra
     * @return array<string,mixed>
     */
    private function base(array $extra = []): array
    {
        $user = $this->currentUser();

        $base = [
            'showHeader' => false,
            'showFooter' => false,
            'isDashboard' => true,
            'str' => $this->translations(),
            'user' => $user,
            'isAdmin' => $this->isAdmin(),
            'links' => $this->sidebarLinks(),
            'flash' => $this->flashMessages(),
        ];

        return array_replace($base, $extra);
    }



    /**
     * Mappe un ArticleDTO en VM pour la liste.
     * @param object $dto
     * @return array{id:int,titre:string,resume:string,date:string,author:string,editUrl:string,deleteUrl:string,showUrl:string}
     */
    private function mapListItem(object $dto): array
    {
        $id = (int)($dto->id ?? 0);
        $dateStr = '';
        if (!empty($dto->date_article)) {
            try {
                $dateStr = (new \DateTime((string)$dto->date_article))->format('d/m/Y');
            } catch (\Throwable) {
                $dateStr = (string)$dto->date_article;
            }
        }

        $editBase = '/dashboard/articles/edit';
        $deleteBase = '/dashboard/articles/delete';
        $showBase = '/dashboard/articles/show';

        return [
            'id' => $id,
            'titre' => (string)($dto->titre ?? ''),
            'resume' => (string)($dto->resume ?? ''),
            'date' => $dateStr,
            'author' => (string)($dto->author ?? 'Inconnu'),
            'editUrl' => rtrim($editBase, '/') . '/' . rawurlencode((string)$id),
            'deleteUrl' => rtrim($deleteBase, '/') . '/' . rawurlencode((string)$id),
            'showUrl' => rtrim($showBase, '/') . '/' . rawurlencode((string)$id),
        ];
    }

    /**
     * Mappe un ArticleDTO/envoi POST en VM pour le formulaire (créa/édition).
     * @param array<string,mixed>|object|null $src
     * @return array<string,mixed>
     */
    private function mapFormData(array|object|null $src): array
    {
        if ($src === null) {
            return [
                'titre' => '',
                'resume' => '',
                'description' => '',
                'date_article' => '',
                'hours' => '',
                'lieu' => '',
            ];
        }
        $a = is_object($src) ? get_object_vars($src) : $src;

        return [
            'titre' => (string)($a['titre'] ?? ''),
            'resume' => (string)($a['resume'] ?? ''),
            'description' => (string)($a['description'] ?? ''),
            'date_article' => (string)($a['date_article'] ?? ''),
            'hours' => (string)($a['hours'] ?? ''),
            'lieu' => (string)($a['lieu'] ?? ''),
        ];
    }

    /* -------------------------------------------------------
     * Routes
     * ----------------------------------------------------- */

    /** GET /dashboard/articles */
    #[Route(path: '', methods: ['GET'])]
    public function index(): Response
    {
        $list = $this->articles->getAll();
        $items = array_map(fn ($dto) => $this->mapListItem($dto), $list);

        return $this->page('dashboard:home', $this->base([
            'title' => 'Articles',
            'component' => 'dashboard/dash_articles', // {{> component:@component }}
            'createUrl' => '/dashboard/articles/create',
            'articles' => $items,
            'csrf_input' => $this->csrfInput(),
        ]));
    }

    /** GET /dashboard/articles/show/{id} */
    #[Route(path: '/show/{id}', methods: ['GET'])]
    public function show(int $id): Response
    {
        $dto = $this->articles->getById($id);
        if (!$dto) {
            return $this->res->text('Not Found', 404);
        }

        $vm = [
            'title' => (string)($dto->titre ?? ''),
            'summary' => (string)($dto->resume ?? ''),
            'description' => (string)($dto->description ?? ''),
            'date' => (string)($dto->date_article ?? ''),
            'time' => substr((string)($dto->hours ?? ''), 0, 5),
            'location' => (string)($dto->lieu ?? ''),
            'author' => (string)($dto->author ?? 'Inconnu'),
            'backUrl' => '/dashboard/articles',
        ];

        return $this->page('dashboard:home', $this->base([
            'title' => 'Détail de l’article',
            'component' => 'dashboard/dash_article_show',
            'article' => $vm,
        ]));
    }

    /** GET /dashboard/articles/create */
    #[Route(path: '/create', methods: ['GET'])]
    public function createForm(): Response
    {
        $data = $this->formData();
        $errors = $this->formErrors();

        return $this->page('dashboard:home', $this->base([
            'title' => 'Créer un article',
            'component' => 'dashboard/dash_article_form',
            'action' => '/dashboard/articles/create',
            'article' => $this->mapFormData($data),
            'errors' => $errors,
            'csrf_input' => $this->csrfInput(),
        ]));
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

        $errors = $this->formErrors();
        $prefill = $this->formData();

        return $this->page('dashboard:home', $this->base([
            'title' => 'Modifier un article',
            'component' => 'dashboard/dash_article_form',
            'action' => "/dashboard/articles/edit/{$id}",
            'article' => $this->mapFormData($prefill ?: get_object_vars($dto)),
            'errors' => $errors,
            'csrf_input' => $this->csrfInput(),
        ]));
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

        // idempotent : delete “silencieux”
        $this->articles->delete($id);

        return $this->redirectWithSuccess('/dashboard/articles', 'Article supprimé.');
    }
}
