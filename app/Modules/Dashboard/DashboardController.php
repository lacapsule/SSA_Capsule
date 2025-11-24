<?php

declare(strict_types=1);

namespace App\Modules\Dashboard;

use App\Providers\SidebarLinksProvider;
use App\Modules\Galerie\GalerieService;
use App\Modules\Galerie\GaleriePresenter;
use Capsule\Contracts\ResponseFactoryInterface;
use Capsule\Contracts\ViewRendererInterface;
use Capsule\Domain\Service\PasswordService;
use Capsule\Http\Message\Response;
use Capsule\Routing\Attribute\Route;
use Capsule\Routing\Attribute\RoutePrefix;
use Capsule\Security\CsrfTokenManager;
use Capsule\Support\Pagination\Page;
use Capsule\Support\Pagination\Paginator;
use Capsule\View\BaseController;

#[RoutePrefix('/dashboard')]
final class DashboardController extends BaseController
{
    //  Configuration du module Dashboard
    protected string $pageNs = 'dashboard';
    protected string $componentNs = 'dashboard';
    protected string $layout = 'dashboard';  // Layout spécifique avec sidebar

    public function __construct(
        private readonly DashboardService $dashboard,
        private readonly PasswordService $passwords,
        private readonly SidebarLinksProvider $links,
        private readonly GalerieService $galerieService,
        ResponseFactoryInterface $res,
        ViewRendererInterface $view,
    ) {
        parent::__construct($res, $view);
    }

    /**
     * Fabrique les données communes du dashboard (shell)
     * @return array<string,mixed>
     */
    private function baseShell(): array
    {
        $i18n = $this->i18n();
        $user = $this->currentUser();
        $isAdmin = $this->isAdmin();
        $links = $this->links->get($isAdmin);

        return DashboardPresenter::base($i18n, $user, $isAdmin, $links);
    }

    /* ---------------- Routes (GET) ---------------- */

    #[Route(path: '', methods: ['GET'])]
    public function index(): Response
    {
        $base = $this->baseShell();

        // Résout vers page:dashboard/index avec layout:dashboard
        return $this->page('index', $base + [
            'title' => 'Dashboard',
        ]);
    }

    #[Route(path: '/galerie', methods: ['GET'])]
    public function galerie(): Response
    {
        $base = $this->baseShell();

        // Récupérer la page depuis la requête
        $paginator = Paginator::fromGlobals(defaultLimit: 24, maxLimit: 24);

        // Obtenir le nombre total d'images
        $totalImages = $this->galerieService->countAllImages();

        // Créer une Page avec le total réel
        $page = new Page(
            page: $paginator->page,
            limit: $paginator->limit,
            total: $totalImages
        );

        // Récupérer les images paginées
        $images = $this->galerieService->getImagePage(
            limit: $page->limit,
            offset: $page->offset()
        );

        // Préparer les données pour le template
        $data = GaleriePresenter::index(
            images: $images,
            page: $page,
            base: $base
        );

        $data['component'] = 'dashboard/components/galerie';
        $data['csrf_input'] = $this->csrfInput();

        return $this->page('index', $data);
    }



    #[Route(path: '/users', methods: ['GET'])]
    public function users(): Response
    {
        $base = $this->baseShell();
        $page = Paginator::fromGlobals(defaultLimit: 20, maxLimit: 200);
        $errors = $this->formErrors();
        $prefill = $this->formData();

        // Domaine
        $usersIt = $this->dashboard->getUsers($page); // iterable<object>

        // Vue (Presenter) — matérialise page-size
        $data = DashboardPresenter::users(
            base: $base,
            users: $usersIt,
            errors: $errors,
            prefill: $prefill,
            csrfInput: $this->csrfInput()
        );

        // Composant dynamique
        $data['component'] = 'dashboard/components/users';

        // Résout vers page:dashboard/index avec component:dashboard/components/users
        return $this->page('index', $data);
    }

    #[Route(path: '/account', methods: ['GET'])]
    public function account(): Response
    {
        $base = $this->baseShell();
        $errors = $this->formErrors();
        $prefill = $this->formData();

        $data = DashboardPresenter::account(
            base: $base,
            errors: $errors,
            prefill: $prefill,
            csrfInput: $this->csrfInput()
        );

        return $this->page('index', $data);
    }

    /* ---------------- Actions (POST) ---------------- */

    #[Route(path: '/account/password', methods: ['POST'])]
    public function accountPassword(): Response
    {
        CsrfTokenManager::requireValidToken();

        $userId = (int) (($this->currentUser()['id'] ?? 0));
        if ($userId <= 0) {
            return $this->res->text('Forbidden', 403);
        }

        $old = trim((string)($_POST['old_password'] ?? ''));
        $new = trim((string)($_POST['new_password'] ?? ''));
        $confirm = trim((string)($_POST['confirm_new_password'] ?? ''));

        $errors = [];
        if ($new === '' || $old === '') {
            $errors['_global'] = 'Champs requis manquants.';
        } elseif ($new !== $confirm) {
            $errors['confirm_new_password'] = 'Les nouveaux mots de passe ne correspondent pas.';
        }

        if ($errors === []) {
            [$ok, $svcErrors] = $this->passwords->changePassword($userId, $old, $new);
            if ($ok) {
                return $this->redirectWithSuccess(
                    '/dashboard/account',
                    'Mot de passe modifié avec succès.'
                );
            }
            $errors = $svcErrors ?: ['_global' => 'Échec de la modification du mot de passe.'];
        }

        return $this->redirectWithErrors(
            '/dashboard/account',
            'Le formulaire contient des erreurs.',
            $errors,
            [] // pas de pré-remplissage sensible ici
        );
    }

    #[Route(path: '/account/email', methods: ['POST'])]
    public function accountEmail(): Response
    {
        CsrfTokenManager::requireValidToken();

        $userId = (int) (($this->currentUser()['id'] ?? 0));
        if ($userId <= 0) {
            return $this->res->text('Forbidden', 403);
        }

        $password = trim((string)($_POST['password'] ?? ''));
        $newEmail = trim((string)($_POST['new_email'] ?? ''));
        $confirmEmail = trim((string)($_POST['confirm_email'] ?? ''));

        $errors = [];
        
        // Validations
        if ($password === '' || $newEmail === '' || $confirmEmail === '') {
            $errors['_global'] = 'Champs requis manquants.';
        } elseif (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
            $errors['new_email'] = 'L\'adresse email n\'est pas valide.';
        } elseif ($newEmail !== $confirmEmail) {
            $errors['confirm_email'] = 'Les adresses email ne correspondent pas.';
        }

        // Vérifier si l'email est déjà utilisé
        if ($errors === []) {
            [$ok, $svcErrors] = $this->passwords->changeEmail($userId, $password, $newEmail);
            if ($ok) {
                return $this->redirectWithSuccess(
                    '/dashboard/account',
                    'Adresse email modifiée avec succès.'
                );
            }
            $errors = $svcErrors ?: ['_global' => 'Échec de la modification de l\'adresse email.'];
        }

        return $this->redirectWithErrors(
            '/dashboard/account',
            'Le formulaire contient des erreurs.',
            $errors,
            [] // pas de pré-remplissage sensible ici
        );
    }

    /* ---------------- Routes Galerie (POST) ---------------- */

    #[Route(path: '/galerie/upload', methods: ['POST'])]
    public function galerieUpload(): Response
    {
        CsrfTokenManager::requireValidToken();

        $isAjax = ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest';

        if (empty($_FILES['photos']) || !is_array($_FILES['photos']['name'])) {
            if ($isAjax) {
                return $this->res->json(['success' => false, 'message' => 'Aucune photo sélectionnée.'], 400);
            }
            return $this->redirectWithErrors(
                '/dashboard/galerie',
                'Aucune photo sélectionnée.',
                ['_global' => 'Aucune photo sélectionnée.']
            );
        }

        // Préparer les fichiers pour le service
        $files = [];
        $customNames = [];
        
        $photos = $_FILES['photos'];
        $count = count($photos['name']);
        
        for ($i = 0; $i < $count; $i++) {
            if ($photos['error'][$i] === UPLOAD_ERR_OK) {
                $files[] = [
                    'tmp_name' => $photos['tmp_name'][$i],
                    'name' => $photos['name'][$i],
                    'type' => $photos['type'][$i],
                    'error' => $photos['error'][$i],
                    'size' => $photos['size'][$i],
                ];
                
                // Récupérer le nom personnalisé si fourni
                $customNames[] = trim((string)($_POST['photo_names'][$i] ?? '')) ?: null;
            }
        }

        if (empty($files)) {
            if ($isAjax) {
                return $this->res->json(['success' => false, 'message' => 'Aucun fichier valide.'], 400);
            }
            return $this->redirectWithErrors(
                '/dashboard/galerie',
                'Aucun fichier valide.',
                ['_global' => 'Aucun fichier valide.']
            );
        }

        $result = $this->galerieService->uploadImages($files, $customNames);

        if ($isAjax) {
            if ($result['success'] > 0) {
                return $this->res->json([
                    'success' => true,
                    'message' => sprintf('%d photo(s) ajoutée(s) avec succès.', $result['success']),
                    'uploaded' => $result['success'],
                    'errors' => $result['errors']
                ]);
            }
            return $this->res->json([
                'success' => false,
                'message' => 'Aucune photo n\'a pu être ajoutée.',
                'errors' => $result['errors']
            ], 400);
        }

        if ($result['success'] > 0) {
            return $this->redirectWithSuccess(
                '/dashboard/galerie',
                sprintf('%d photo(s) ajoutée(s) avec succès.', $result['success'])
            );
        }

        return $this->redirectWithErrors(
            '/dashboard/galerie',
            'Aucune photo n\'a pu être ajoutée.',
            $result['errors']
        );
    }

    #[Route(path: '/galerie/delete', methods: ['POST'])]
    public function galerieDelete(): Response
    {
        CsrfTokenManager::requireValidToken();

        $isAjax = ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest';

        $filename = trim((string)($_POST['filename'] ?? ''));
        
        if ($filename === '') {
            if ($isAjax) {
                return $this->res->json(['success' => false, 'message' => 'Nom de fichier manquant.'], 400);
            }
            return $this->redirectWithErrors(
                '/dashboard/galerie',
                'Nom de fichier manquant.',
                ['_global' => 'Nom de fichier manquant.']
            );
        }

        $deleted = $this->galerieService->deleteImage($filename);

        if ($isAjax) {
            if ($deleted) {
                return $this->res->json(['success' => true, 'message' => 'Photo supprimée avec succès.']);
            }
            return $this->res->json(['success' => false, 'message' => 'Impossible de supprimer la photo.'], 400);
        }

        if ($deleted) {
            return $this->redirectWithSuccess('/dashboard/galerie', 'Photo supprimée avec succès.');
        }

        return $this->redirectWithErrors(
            '/dashboard/galerie',
            'Impossible de supprimer la photo.',
            ['_global' => 'Impossible de supprimer la photo.']
        );
    }

    #[Route(path: '/galerie/delete-batch', methods: ['POST'])]
    public function galerieDeleteBatch(): Response
    {
        CsrfTokenManager::requireValidToken();

        $isAjax = ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest';

        $filenames = $_POST['filenames'] ?? [];
        
        if (!is_array($filenames) || empty($filenames)) {
            if ($isAjax) {
                return $this->res->json(['success' => false, 'message' => 'Aucune photo sélectionnée.'], 400);
            }
            return $this->redirectWithErrors(
                '/dashboard/galerie',
                'Aucune photo sélectionnée.',
                ['_global' => 'Aucune photo sélectionnée.']
            );
        }

        // Nettoyer les filenames
        $filenames = array_map('trim', $filenames);
        $filenames = array_filter($filenames, fn($f) => $f !== '');

        if (empty($filenames)) {
            if ($isAjax) {
                return $this->res->json(['success' => false, 'message' => 'Aucune photo valide sélectionnée.'], 400);
            }
            return $this->redirectWithErrors(
                '/dashboard/galerie',
                'Aucune photo valide sélectionnée.',
                ['_global' => 'Aucune photo valide sélectionnée.']
            );
        }

        $result = $this->galerieService->deleteImages(array_values($filenames));

        if ($isAjax) {
            if ($result['success'] > 0) {
                return $this->res->json([
                    'success' => true,
                    'message' => sprintf('%d photo(s) supprimée(s) avec succès.', $result['success']),
                    'deleted' => $result['success'],
                    'failed' => $result['failed']
                ]);
            }
            return $this->res->json([
                'success' => false,
                'message' => 'Aucune photo n\'a pu être supprimée.',
                'failed' => $result['failed']
            ], 400);
        }

        if ($result['success'] > 0) {
            return $this->redirectWithSuccess(
                '/dashboard/galerie',
                sprintf('%d photo(s) supprimée(s) avec succès.', $result['success'])
            );
        }

        return $this->redirectWithErrors(
            '/dashboard/galerie',
            'Aucune photo n\'a pu être supprimée.',
            ['_global' => 'Aucune photo n\'a pu être supprimée.']
        );
    }

    #[Route(path: '/galerie/rename', methods: ['POST'])]
    public function galerieRename(): Response
    {
        CsrfTokenManager::requireValidToken();

        $isAjax = ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest';

        $filename = trim((string)($_POST['filename'] ?? ''));
        $newAlt = trim((string)($_POST['alt'] ?? ''));

        if ($filename === '' || $newAlt === '') {
            if ($isAjax) {
                return $this->res->json(['success' => false, 'message' => 'Champs requis manquants.'], 400);
            }
            return $this->redirectWithErrors(
                '/dashboard/galerie',
                'Champs requis manquants.',
                ['_global' => 'Champs requis manquants.']
            );
        }

        // Générer le nouveau filename à partir du nom personnalisé
        $pathInfo = pathinfo($filename);
        $extension = $pathInfo['extension'] ?? 'webp';
        
        // Nettoyer le nom
        $cleanName = preg_replace('/[^a-zA-Z0-9_-]/', '-', $newAlt);
        $cleanName = preg_replace('/-+/', '-', $cleanName);
        $cleanName = trim($cleanName, '-');
        
        if ($cleanName === '') {
            $cleanName = 'image';
        }

        $newFilename = $cleanName . '.' . $extension;

        // Si le nom n'a pas changé, on considère que c'est juste une mise à jour du alt
        if ($newFilename === $filename) {
            if ($isAjax) {
                return $this->res->json(['success' => true, 'message' => 'Nom mis à jour.']);
            }
            return $this->redirectWithSuccess('/dashboard/galerie', 'Nom mis à jour.');
        }

        $renamed = $this->galerieService->renameImage($filename, $newFilename);

        if ($isAjax) {
            if ($renamed) {
                return $this->res->json(['success' => true, 'message' => 'Photo renommée avec succès.', 'newFilename' => $newFilename]);
            }
            return $this->res->json(['success' => false, 'message' => 'Impossible de renommer la photo.'], 400);
        }

        if ($renamed) {
            return $this->redirectWithSuccess('/dashboard/galerie', 'Photo renommée avec succès.');
        }

        return $this->redirectWithErrors(
            '/dashboard/galerie',
            'Impossible de renommer la photo.',
            ['_global' => 'Impossible de renommer la photo.']
        );
    }
}
