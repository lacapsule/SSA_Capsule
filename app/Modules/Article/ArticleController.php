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
use App\Support\ImageConverter;
use Capsule\View\BaseController;

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
        // Log CSRF attempt for debugging
        $isAjax = $_SERVER['HTTP_X_REQUESTED_WITH'] ?? '' === 'XMLHttpRequest';
        error_log("🔒 CSRF Check - POST Data: " . json_encode(array_keys($_POST)) . " | AJAX: " . ($isAjax ? 'yes' : 'no') . " | Session: " . session_id());
        
        CsrfTokenManager::requireValidToken();

        $current = $this->currentUser();

        // Handle uploaded image if present
        if (!empty($_FILES['image']) && ($_FILES['image']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
            try {
                $uploaded = ImageConverter::convertUploadedFile($_FILES['image']);
                if ($uploaded !== null) {
                    // store web path in POST for service
                    $_POST['image'] = $uploaded;
                }
            } catch (\Throwable $e) {
                error_log("❌ Image conversion error: " . $e->getMessage());
                return $this->res->json(['success' => false, 'errors' => ['image' => ['Erreur lors du traitement de l\'image: ' . $e->getMessage()]]], 400);
            }
        }

        $result = $this->articles->create($_POST, $current);

            // Détection requête AJAX
            $isAjax = $_SERVER['HTTP_X_REQUESTED_WITH'] ?? '' === 'XMLHttpRequest' || 
                      !empty($_POST) && (strpos($_SERVER['CONTENT_TYPE'] ?? '', 'application/x-www-form-urlencoded') === 0 || 
                      strpos($_SERVER['CONTENT_TYPE'] ?? '', 'multipart/form-data') === 0);

            if (!empty($result['errors'])) {
                if ($isAjax) {
                    return $this->res->json(['success' => false, 'errors' => $result['errors']], 400);
                }
                return $this->redirectWithErrors(
                    '/dashboard/articles/create',
                    'Le formulaire contient des erreurs.',
                    $result['errors'],
                    $result['data'] ?? $_POST
                );
            }

            if ($isAjax) {
                return $this->res->json(['success' => true, 'message' => 'Article créé avec succès.']);
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
        // Log CSRF attempt for debugging
        error_log("🔒 CSRF Check Edit - POST Data: " . json_encode(array_keys($_POST)) . " | Session: " . session_id());
        
        CsrfTokenManager::requireValidToken();

        if (!$this->articles->getById($id)) {
            return $this->res->text('Not Found', 404);
        }

        // Récupérer ancienne image si besoin
        $dto = $this->articles->getById($id);
        $oldImage = $dto?->image ?? null;

        // Handle uploaded image if present
        $uploaded = null;
        if (!empty($_FILES['image']) && ($_FILES['image']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
            try {
                $uploaded = ImageConverter::convertUploadedFile($_FILES['image']);
                if ($uploaded !== null) {
                    $_POST['image'] = $uploaded;
                }
            } catch (\Throwable $e) {
                error_log("❌ Image conversion error: " . $e->getMessage());
                return $this->res->json(['success' => false, 'errors' => ['image' => ['Erreur lors du traitement de l\'image: ' . $e->getMessage()]]], 400);
            }
        }

        $result = $this->articles->update($id, $_POST);
            // Détection requête AJAX
            $isAjax = $_SERVER['HTTP_X_REQUESTED_WITH'] ?? '' === 'XMLHttpRequest' || 
                      !empty($_POST) && (strpos($_SERVER['CONTENT_TYPE'] ?? '', 'application/x-www-form-urlencoded') === 0 || 
                      strpos($_SERVER['CONTENT_TYPE'] ?? '', 'multipart/form-data') === 0);

            if (!empty($result['errors'])) {
                if ($isAjax) {
                    return $this->res->json(['success' => false, 'errors' => $result['errors']], 400);
                }
                return $this->redirectWithErrors(
                    "/dashboard/articles/edit/{$id}",
                    'Le formulaire contient des erreurs.',
                    $result['errors'],
                    $result['data'] ?? $_POST
                );
            }

            if ($isAjax) {
                // If we uploaded a new image, remove the old file from disk (only if it was stored in articles dir)
                if ($uploaded && $oldImage && str_starts_with($oldImage, '/assets/img/articles/')) {
                    $oldPath = __DIR__ . '/../../..' . $oldImage;
                    if (is_file($oldPath)) {
                        @unlink($oldPath);
                    }
                }

                return $this->res->json(['success' => true, 'message' => 'Article modifié avec succès.']);
            }
            // Cleanup old image when not AJAX as well
            if ($uploaded && $oldImage && str_starts_with($oldImage, '/assets/img/articles/')) {
                $oldPath = __DIR__ . '/../../..' . $oldImage;
                if (is_file($oldPath)) {
                    @unlink($oldPath);
                }
            }

            return $this->redirectWithSuccess('/dashboard/articles', 'Article mis à jour.');
    }

    // image conversion is delegated to App\Support\ImageConverter

    /** POST /dashboard/articles/delete/{id} */
    #[Route(path: '/delete/{id}', methods: ['POST'])]
    public function deleteSubmit(int $id): Response
    {
        CsrfTokenManager::requireValidToken();

        // idempotent : delete "silencieux"
        $this->articles->delete($id);

            // Détection requête AJAX
            $isAjax = $_SERVER['HTTP_X_REQUESTED_WITH'] ?? '' === 'XMLHttpRequest' || 
                      !empty($_POST) && strpos($_SERVER['CONTENT_TYPE'] ?? '', 'application/x-www-form-urlencoded') === 0;

            if ($isAjax) {
                return $this->res->json(['success' => true, 'message' => 'Article supprimé avec succès.']);
            }

            return $this->redirectWithSuccess('/dashboard/articles', 'Article supprimé.');
    }

    /** GET /dashboard/articles/api/{id} - Récupère les détails complets d'un article */
    #[Route(path: '/api/{id}', methods: ['GET'])]
    public function getArticle(int $id): Response
    {
        try {
            $dto = $this->articles->getById($id);
            
            if (!$dto) {
                return $this->res->json([
                    'success' => false,
                    'message' => 'Article non trouvé'
                ], 404);
            }

            return $this->res->json([
                'success' => true,
                'article' => [
                    'id' => $dto->id,
                    'titre' => $dto->titre,
                    'resume' => $dto->resume,
                    'description' => $dto->description,
                    'date_article' => $dto->date_article,
                    'hours' => $dto->hours,
                    'lieu' => $dto->lieu,
                    'image' => $dto->image,
                ]
            ]);
        } catch (\Throwable $e) {
            return $this->res->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération de l\'article'
            ], 500);
        }
    }
}
