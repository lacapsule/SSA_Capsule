<?php

namespace App\Modules\Galerie;

use Capsule\Contracts\ResponseFactoryInterface;
use Capsule\Contracts\ViewRendererInterface;
use Capsule\Http\Message\Response;
use Capsule\Routing\Attribute\Route;
use Capsule\View\BaseController;

final class GalerieController extends BaseController
{
    protected string $pageNs = 'galerie';           // Résout page:galerie/index
    protected string $componentNs = 'galerie';      // Résout component:galerie
    protected string $layout = 'main';           // Layout public par défaut

    public function __construct(
        ResponseFactoryInterface $res,
        ViewRendererInterface $view
    ) {
        parent::__construct($res, $view);
    }

    #[Route(path: '/galerie', methods: ['GET'])]
    public function galerie(): Response
    {
        // Images statiques pour l’exemple
        $pictures = [];
        for ($i = 1; $i <= 191; $i++) {
            $pictures[] = [
                'src' => "/assets/img/gallery/image_{$i}.webp",
                'alt' => "Image {$i}",
            ];
        }

        $imagesPerPage = 25;
        $totalImages = count($pictures);
        $totalPages = (int)ceil($totalImages / $imagesPerPage);

        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $page = min($page, $totalPages);

        $startIndex = ($page - 1) * $imagesPerPage;
        $picturesPage = array_slice($pictures, $startIndex, $imagesPerPage);

        return $this->page('index', [
            'showHeader' => true,
            'showFooter' => true,
            'str' => $this->i18n(),
            'pictures' => $picturesPage,
            'pagination' => [
                'current' => $page,
                'total' => $totalPages,
                'hasPrev' => $page > 1,
                'hasNext' => $page < $totalPages,
                'prev' => $page - 1,
                'next' => $page + 1,
            ],
        ]);
    }
}
