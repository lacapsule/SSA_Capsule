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
use Capsule\View\Safe;

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

        $imageFiles = $this->collectUploadedImages('images');
        if ($imageFiles === [] && isset($_FILES['image'])) {
            $imageFiles = $this->collectUploadedImages('image');
        }

        $result = $this->articles->create($_POST, $current, $imageFiles);

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

        $newImages = $this->collectUploadedImages('images');
        if ($newImages === [] && isset($_FILES['image'])) {
            $newImages = $this->collectUploadedImages('image');
        }

        $result = $this->articles->update($id, $_POST, $newImages);
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
                return $this->res->json(['success' => true, 'message' => 'Article modifié avec succès.']);
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
                    'image' => $dto->image ? (string) Safe::imageUrl((string) $dto->image) : '',
                    'images' => array_map(
                        static fn (string $path): string => (string) Safe::imageUrl($path),
                        $dto->images
                    ),
                    'media' => array_map(
                        fn (array $media): array => [
                            'id' => (int) $media['id'],
                            'path' => $media['path'],
                            'filename' => basename((string) $media['path']),
                            'src' => (string) Safe::imageUrl((string) $media['path']),
                            'isVideo' => self::isVideoPath((string) $media['path']),
                        ],
                        $this->articles->getMediaList($id)
                    ),
                ]
            ]);
        } catch (\Throwable $e) {
            return $this->res->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération de l\'article'
            ], 500);
        }
    }

    #[Route(path: '/{articleId}/media/{mediaId}/delete', methods: ['POST'])]
    public function deleteMedia(int $articleId, int $mediaId): Response
    {
        CsrfTokenManager::requireValidToken();
        $result = $this->articles->deleteMedia($articleId, $mediaId);

        if (!empty($result['error'])) {
            return $this->res->json(['success' => false, 'message' => $result['error']], 400);
        }

        return $this->res->json(['success' => true]);
    }

    #[Route(path: '/{articleId}/media/{mediaId}/rename', methods: ['POST'])]
    public function renameMedia(int $articleId, int $mediaId): Response
    {
        CsrfTokenManager::requireValidToken();
        $newName = $_POST['name'] ?? '';
        $result = $this->articles->renameMedia($articleId, $mediaId, (string) $newName);

        if (!empty($result['error'])) {
            return $this->res->json(['success' => false, 'message' => $result['error']], 400);
        }

        $path = (string) ($result['path'] ?? '');

        return $this->res->json([
            'success' => true,
            'media' => [
                'id' => $mediaId,
                'path' => $path,
                'filename' => basename($path),
                'src' => (string) Safe::imageUrl($path),
                'isVideo' => self::isVideoPath($path),
            ],
        ]);
    }

    /**
     * @return array<int, array{tmp_name:string,name:string,type:string,error:int,size:int}>
     */
    private function collectUploadedImages(string $fieldName): array
    {
        if (!isset($_FILES[$fieldName])) {
            return [];
        }

        $field = $_FILES[$fieldName];
        $normalized = [];

        if (is_array($field['name'])) {
            foreach ($field['name'] as $idx => $name) {
                $normalized[] = [
                    'name' => $name,
                    'type' => $field['type'][$idx] ?? '',
                    'tmp_name' => $field['tmp_name'][$idx] ?? '',
                    'error' => $field['error'][$idx] ?? UPLOAD_ERR_NO_FILE,
                    'size' => $field['size'][$idx] ?? 0,
                ];
            }
        } else {
            $normalized[] = [
                'name' => $field['name'] ?? '',
                'type' => $field['type'] ?? '',
                'tmp_name' => $field['tmp_name'] ?? '',
                'error' => $field['error'] ?? UPLOAD_ERR_NO_FILE,
                'size' => $field['size'] ?? 0,
            ];
        }

        return array_values(array_filter(
            $normalized,
            static fn (array $file): bool => ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK
        ));
    }
    private static function isVideoPath(string $path): bool
    {
        $target = parse_url($path, PHP_URL_PATH) ?: $path;
        $ext = strtolower((string) pathinfo((string) $target, PATHINFO_EXTENSION));

        return in_array($ext, ['mp4', 'webm', 'ogv', 'ogg', 'mov'], true);
    }
}
