<?php

declare(strict_types=1);

namespace App\Modules\Projet;

use App\Providers\SidebarLinksProvider;
use Capsule\Contracts\ResponseFactoryInterface;
use Capsule\Contracts\ViewRendererInterface;
use Capsule\Http\Message\Response;
use Capsule\Routing\Attribute\Route;
use Capsule\Routing\Attribute\RoutePrefix;
use Capsule\Security\CsrfTokenManager;
use Capsule\View\BaseController;

#[RoutePrefix('/dashboard/projet')]
final class ProjectAdminController extends BaseController
{
    protected string $pageNs = 'dashboard';
    protected string $componentNs = 'dashboard';
    protected string $layout = 'dashboard';

    public function __construct(
        private ProjectMediaService $mediaService,
        private SidebarLinksProvider $linksProvider,
        ResponseFactoryInterface $res,
        ViewRendererInterface $view,
    ) {
        parent::__construct($res, $view);
    }

    #[Route(path: '', methods: ['GET'])]
    public function edit(): Response
    {
        $media = $this->mediaService->getMedia();

        return $this->page('index', [
            'title' => 'Médias du projet',
            'component' => 'dashboard/components/projet-media',
            'links' => $this->linksProvider->get($this->isAdmin()),
            'user' => $this->currentUser(),
            'isAdmin' => $this->isAdmin(),
            'str' => $this->i18n(),
            'media' => $media,
            'csrf_input' => $this->csrfInput(),
        ]);
    }

    #[Route(path: '', methods: ['POST'])]
    public function update(): Response
    {
        CsrfTokenManager::requireValidToken();

        $files = [
            'hero' => $_FILES['hero'] ?? null,
            'illustration_top' => $_FILES['illustration_top'] ?? null,
            'illustration_bottom' => $_FILES['illustration_bottom'] ?? null,
        ];
        $files = array_filter($files, static fn($f) => $f !== null);

        $result = $this->mediaService->update($files);

        if (($result['success'] ?? false) === false) {
            return $this->redirectWithErrors(
                '/dashboard/projet',
                'Échec de la mise à jour des médias.',
                $result['errors'] ?? [],
                []
            );
        }

        return $this->redirectWithSuccess('/dashboard/projet', 'Médias mis à jour.');
    }
}

