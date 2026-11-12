<?php

namespace App\Modules\Legal;

use Capsule\Contracts\ResponseFactoryInterface;
use Capsule\Contracts\ViewRendererInterface;
use Capsule\Http\Message\Response;
use Capsule\Routing\Attribute\Route;
use Capsule\View\BaseController;

final class LegalController extends BaseController
{
    protected string $pageNs = 'legal';
    protected string $layout = 'main';

    public function __construct(
        ResponseFactoryInterface $res,
        ViewRendererInterface $view,
    ) {
        parent::__construct($res, $view);
    }

    #[Route(path: '/mentions-legales', methods: ['GET'])]
    public function mentions(): Response
    {
        return $this->page('mentions-legales', $this->common());
    }

    #[Route(path: '/confidentialite', methods: ['GET'])]
    public function confidentialite(): Response
    {
        return $this->page('confidentialite', $this->common());
    }

    #[Route(path: '/accessibilite', methods: ['GET'])]
    public function accessibilite(): Response
    {
        return $this->page('accessibilite', $this->common());
    }

    /**
     * @return array<string,mixed>
     */
    private function common(): array
    {
        return [
            'showHeader' => true,
            'showFooter' => true,
            'str' => $this->i18n(),
            'isAuthenticated' => $this->isAuthenticated(),
        ];
    }
}
