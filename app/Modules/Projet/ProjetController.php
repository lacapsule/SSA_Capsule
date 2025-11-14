<?php

namespace App\Modules\Projet;

use Capsule\Contracts\ResponseFactoryInterface;
use Capsule\Contracts\ViewRendererInterface;
use Capsule\Http\Message\Response;
use Capsule\Routing\Attribute\Route;
use Capsule\View\BaseController;

final class ProjetController extends BaseController
{
    protected string $pageNs = 'projet';           // Résout page:projet/index
    protected string $componentNs = 'projet';      // Résout component:projet/actualites
    protected string $layout = 'main';           // Layout public par défaut

    public function __construct(
        ResponseFactoryInterface $res,
        ViewRendererInterface $view
    ) {
        parent::__construct($res, $view);
    }


    #[Route(path: '/projet', methods: ['GET'])]
    public function projet(): Response
    {
        return $this->page('index', [
            'showHeader' => true,
            'showFooter' => true,
            'str' => $this->i18n(),
            'isAuthenticated' => $this->isAuthenticated(),
        ]);
    }
}
