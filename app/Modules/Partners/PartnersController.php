<?php

declare(strict_types=1);

namespace App\Modules\Partners;

use App\Modules\Dashboard\DashboardPresenter;
use App\Providers\SidebarLinksProvider;
use Capsule\Contracts\ResponseFactoryInterface;
use Capsule\Contracts\ViewRendererInterface;
use Capsule\Domain\Service\PartnersService;
use Capsule\Http\Message\Response;
use Capsule\Routing\Attribute\Route;
use Capsule\Routing\Attribute\RoutePrefix;
use Capsule\Security\CsrfTokenManager;
use Capsule\View\BaseController;

#[RoutePrefix('/dashboard/partners')]
final class PartnersController extends BaseController
{
    protected string $pageNs = 'dashboard';
    protected string $componentNs = 'dashboard';
    protected string $layout = 'dashboard';

    public function __construct(
        private readonly PartnersService $partners,
        private readonly SidebarLinksProvider $linksProvider,
        ResponseFactoryInterface $res,
        ViewRendererInterface $view,
    ) {
        parent::__construct($res, $view);
    }

    #[Route(path: '', methods: ['GET'])]
    public function index(): Response
    {
        $base = $this->baseShell();
        $flash = $this->flashMessages();
        $sections = $this->partners->getSectionsWithLogos();

        // Add logos count for display
        foreach ($sections as &$section) {
            $section['logos_count'] = count($section['logos'] ?? []);
        }

        $data = DashboardPresenter::partners(
            base: $base,
            sections: $sections,
            kinds: [
                'partenaire' => 'Partenaires',
                'financeur' => 'Financeurs',
            ],
            csrfInput: $this->csrfInput(),
            flash: $flash,
            errors: $this->formErrors(),
            old: $this->formData()
        );

        return $this->page('index', $data);
    }

    #[Route(path: '/sections/{id}', methods: ['GET'])]
    public function getSection(int $id): Response
    {
        $section = $this->partners->getSection($id);
        if ($section === null) {
            return $this->res->json(['error' => 'Section introuvable'], 404);
        }

        return $this->res->json($section);
    }

    #[Route(path: '/sections/{id}/logos', methods: ['GET'])]
    public function getSectionLogos(int $id): Response
    {
        $section = $this->partners->getSection($id);
        if ($section === null) {
            return $this->res->json(['error' => 'Section introuvable'], 404);
        }

        $sections = $this->partners->getSectionsWithLogos();
        $sectionData = null;
        foreach ($sections as $s) {
            if ((int)$s['id'] === $id) {
                $sectionData = $s;
                break;
            }
        }

        if ($sectionData === null) {
            return $this->res->json(['error' => 'Section introuvable'], 404);
        }

        return $this->res->json(['logos' => $sectionData['logos'] ?? []]);
    }

    #[Route(path: '/sections', methods: ['POST'])]
    public function createSection(): Response
    {
        CsrfTokenManager::requireValidToken();
        $name = trim((string)($_POST['name'] ?? ''));
        $kind = (string)($_POST['kind'] ?? 'partenaire');

        if ($name === '') {
            return $this->redirectWithErrors(
                '/dashboard/partners',
                'Merci d’indiquer un nom de section.',
                ['name' => 'Nom requis.'],
                ['name' => $name, 'kind' => $kind, 'description' => (string)($_POST['description'] ?? '')]
            );
        }

        try {
            $this->partners->createSection([
                'name' => $name,
                'description' => (string)($_POST['description'] ?? ''),
                'kind' => $kind,
                'position' => (int)($_POST['position'] ?? 0),
                'is_active' => isset($_POST['is_active']) ? 1 : 0,
            ]);
        } catch (\Throwable $e) {
            return $this->redirectWithErrors(
                '/dashboard/partners',
                'Impossible de créer la section.',
                ['_global' => 'Erreur : ' . $e->getMessage()],
                $_POST
            );
        }

        return $this->redirectWithSuccess('/dashboard/partners', 'Section créée.');
    }

    #[Route(path: '/sections/{id}/update', methods: ['POST'])]
    public function updateSection(int $id): Response
    {
        CsrfTokenManager::requireValidToken();

        $name = trim((string)($_POST['name'] ?? ''));
        $description = trim((string)($_POST['description'] ?? ''));
        $kind = (string)($_POST['kind'] ?? '');
        $position = isset($_POST['position']) && $_POST['position'] !== '' ? (int)$_POST['position'] : null;
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        if ($name === '') {
            return $this->redirectWithErrors(
                '/dashboard/partners',
                'Le nom de la section est requis.',
                ['name' => 'Nom requis.'],
                ['name' => $name, 'description' => $description, 'kind' => $kind, 'position' => $position, 'is_active' => $isActive]
            );
        }

        try {
            $this->partners->updateSection($id, [
                'name' => $name,
                'description' => $description !== '' ? $description : null,
                'kind' => $kind !== '' ? $kind : null,
                'position' => $position,
                'is_active' => $isActive,
            ]);
        } catch (\Throwable $e) {
            return $this->redirectWithErrors(
                '/dashboard/partners',
                'Impossible de mettre à jour la section.',
                ['_global' => $e->getMessage()],
                ['name' => $name, 'description' => $description, 'kind' => $kind, 'position' => $position, 'is_active' => $isActive]
            );
        }

        return $this->redirectWithSuccess('/dashboard/partners', 'Section mise à jour.');
    }

    #[Route(path: '/sections/{id}/delete', methods: ['POST'])]
    public function deleteSection(int $id): Response
    {
        CsrfTokenManager::requireValidToken();
        try {
            $this->partners->deleteSection($id);
        } catch (\Throwable $e) {
            return $this->redirectWithErrors(
                '/dashboard/partners',
                'Suppression impossible.',
                ['_global' => 'Erreur : ' . $e->getMessage()],
            );
        }

        return $this->redirectWithSuccess('/dashboard/partners', 'Section supprimée.');
    }

    #[Route(path: '/sections/{id}/logo', methods: ['POST'])]
    public function addLogo(int $id): Response
    {
        CsrfTokenManager::requireValidToken();

        $isAjax = ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest';

        // Support for multiple logos (like gallery)
        if (!empty($_FILES['logos']) && is_array($_FILES['logos']['name'])) {
            // Multiple logos upload
            $logos = $_FILES['logos'];
            $logoNames = $_POST['logo_names'] ?? [];
            $logoUrls = $_POST['logo_urls'] ?? [];
            $logoPositions = $_POST['logo_positions'] ?? [];
            
            $count = count($logos['name']);
            $success = 0;
            $errors = [];
            
            for ($i = 0; $i < $count; $i++) {
                if ($logos['error'][$i] !== UPLOAD_ERR_OK) {
                    $errors[$i] = 'Erreur lors de l\'upload du fichier';
                    continue;
                }
                
                $logoName = trim((string)($logoNames[$i] ?? ''));
                $logoUrl = trim((string)($logoUrls[$i] ?? ''));
                $logoPosition = isset($logoPositions[$i]) && $logoPositions[$i] !== '' ? (int)$logoPositions[$i] : null;
                
                if ($logoName === '') {
                    $errors[$i] = 'Le nom du logo est requis.';
                    continue;
                }
                
                if ($logoUrl === '' || !filter_var($logoUrl, FILTER_VALIDATE_URL)) {
                    $errors[$i] = 'Une URL valide est requise.';
                    continue;
                }
                
                $file = [
                    'tmp_name' => $logos['tmp_name'][$i],
                    'name' => $logos['name'][$i],
                    'type' => $logos['type'][$i],
                    'error' => $logos['error'][$i],
                    'size' => $logos['size'][$i],
                ];
                
                try {
                    $this->partners->createLogo($id, [
                        'name' => $logoName,
                        'url' => $logoUrl,
                        'position' => $logoPosition ?? 0,
                        'custom_name' => $logoName, // Pour le nom du fichier
                    ], $file);
                    $success++;
                } catch (\Throwable $e) {
                    $errors[$i] = $e->getMessage();
                    // Log pour debug
                    error_log("Erreur upload logo #{$i}: " . $e->getMessage() . "\n" . $e->getTraceAsString());
                }
            }
            
            if ($isAjax) {
                if ($success > 0) {
                    return $this->res->json(['success' => true, 'message' => "$success logo(s) ajouté(s) avec succès.", 'errors' => $errors]);
                }
                return $this->res->json(['success' => false, 'error' => 'Aucun logo n\'a pu être ajouté.', 'errors' => $errors], 400);
            }
            
            if ($success > 0) {
                return $this->redirectWithSuccess('/dashboard/partners', "$success logo(s) ajouté(s).");
            }
            return $this->redirectWithErrors('/dashboard/partners', 'Aucun logo n\'a pu être ajouté.', ['_global' => 'Erreurs lors de l\'ajout des logos.']);
        }
        
        // Single logo upload (backward compatibility)
        if (!isset($_FILES['logo']) || ($_FILES['logo']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            if ($isAjax) {
                return $this->res->json(['success' => false, 'error' => 'Merci de sélectionner un fichier valide.'], 400);
            }
            return $this->redirectWithErrors(
                '/dashboard/partners',
                'Merci de sélectionner un fichier valide.',
                ['logo' => 'Logo requis.']
            );
        }

        $name = trim((string)($_POST['logo_name'] ?? ''));
        $url = trim((string)($_POST['logo_url'] ?? ''));

        $errors = [];
        if ($name === '') {
            $errors['logo_name'] = 'Le nom est requis.';
        }
        if ($url === '' || !filter_var($url, FILTER_VALIDATE_URL)) {
            $errors['logo_url'] = 'Une URL valide est requise.';
        }

        if ($errors !== []) {
            if ($isAjax) {
                return $this->res->json(['success' => false, 'error' => 'Vérifiez les champs du formulaire.', 'errors' => $errors], 400);
            }
            return $this->redirectWithErrors(
                '/dashboard/partners',
                'Vérifiez les champs du formulaire.',
                $errors,
                ['logo_name' => $name, 'logo_url' => $url, 'logo_position' => $_POST['logo_position'] ?? 0]
            );
        }

        try {
            $this->partners->createLogo($id, [
                'name' => $name,
                'url' => $url,
                'position' => isset($_POST['logo_position']) && $_POST['logo_position'] !== '' ? (int)$_POST['logo_position'] : 0,
            ], $_FILES['logo']);
            
            if ($isAjax) {
                return $this->res->json(['success' => true, 'message' => 'Logo ajouté avec succès.']);
            }
        } catch (\Throwable $e) {
            if ($isAjax) {
                return $this->res->json(['success' => false, 'error' => $e->getMessage()], 400);
            }
            return $this->redirectWithErrors(
                '/dashboard/partners',
                'Ajout impossible.',
                ['_global' => $e->getMessage()],
                ['logo_name' => $name, 'logo_url' => $url, 'logo_position' => $_POST['logo_position'] ?? 0]
            );
        }

        return $this->redirectWithSuccess('/dashboard/partners', 'Logo ajouté.');
    }

    #[Route(path: '/logos/{id}/update', methods: ['POST'])]
    public function updateLogo(int $id): Response
    {
        CsrfTokenManager::requireValidToken();

        $name = isset($_POST['logo_name']) ? trim((string)$_POST['logo_name']) : null;
        $url = isset($_POST['logo_url']) ? trim((string)$_POST['logo_url']) : null;
        $position = isset($_POST['logo_position']) && $_POST['logo_position'] !== '' ? (int)$_POST['logo_position'] : null;

        $errors = [];
        if ($name !== null && $name === '') {
            $errors['logo_name'] = 'Le nom ne peut pas être vide.';
        }
        if ($url !== null && ($url === '' || !filter_var($url, FILTER_VALIDATE_URL))) {
            $errors['logo_url'] = 'Une URL valide est requise.';
        }

        if ($errors !== []) {
            return $this->redirectWithErrors(
                '/dashboard/partners',
                'Vérifiez les champs du formulaire.',
                $errors
            );
        }

        $file = $_FILES['logo'] ?? null;
        $uploadedFile = $file && ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK ? $file : null;

        try {
            $this->partners->updateLogo($id, [
                'name' => $name,
                'url' => $url,
                'position' => $position,
            ], $uploadedFile);
        } catch (\Throwable $e) {
            return $this->redirectWithErrors(
                '/dashboard/partners',
                'Mise à jour impossible.',
                ['_global' => $e->getMessage()],
            );
        }

        return $this->redirectWithSuccess('/dashboard/partners', 'Logo mis à jour.');
    }

    #[Route(path: '/logos/{id}/delete', methods: ['POST'])]
    public function deleteLogo(int $id): Response
    {
        CsrfTokenManager::requireValidToken();
        try {
            $this->partners->deleteLogo($id);
        } catch (\Throwable $e) {
            return $this->redirectWithErrors(
                '/dashboard/partners',
                'Suppression impossible.',
                ['_global' => 'Erreur : ' . $e->getMessage()],
            );
        }

        return $this->redirectWithSuccess('/dashboard/partners', 'Logo supprimé.');
    }

    private function baseShell(): array
    {
        $i18n = $this->i18n();
        $user = $this->currentUser();
        $isAdmin = $this->isAdmin();
        $links = $this->linksProvider->get($isAdmin);

        return DashboardPresenter::base($i18n, $user, $isAdmin, $links);
    }
}


