<?php

namespace App\Modules\Galerie;

use Capsule\Contracts\ResponseFactoryInterface;
use Capsule\Contracts\ViewRendererInterface;
use Capsule\Http\Message\Response;
use Capsule\Routing\Attribute\Route;
use Capsule\Support\Pagination\Page;
use Capsule\Support\Pagination\Paginator;
use Capsule\View\BaseController;

final class GalerieController extends BaseController
{
    protected string $pageNs = 'galerie';
    protected string $componentNs = 'galerie';
    protected string $layout = 'main';

    public function __construct(
        private readonly GalerieService $galerieService,
        ResponseFactoryInterface $res,
        ViewRendererInterface $view
    ) {
        parent::__construct($res, $view);
    }


    #[Route(path: '/galerie', methods: ['GET'])]
    public function galerie(): Response
    {
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
            base: [
                'showHeader' => true,
                'showFooter' => true,
                'str' => $this->i18n(),
                'isAuthenticated' => $this->isAuthenticated(),
            ]
        );

        return $this->page('index', $data);
    }
}
