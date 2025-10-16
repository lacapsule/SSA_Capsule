<?php

declare(strict_types=1);

namespace App\Modules\Home;

use App\Modules\Article\ArticleService;
use App\Providers\LanguageOptionsProvider;
use Capsule\Support\Pagination\Paginator;
use Capsule\Contracts\ResponseFactoryInterface;
use Capsule\Contracts\ViewRendererInterface;
use Capsule\Routing\Attribute\Route;
use Capsule\View\BaseController;
use Capsule\Http\Message\Response;
use Capsule\View\Safe;

final class HomeController extends BaseController
{
    // Configuration du module Home
    protected string $pageNs = 'home';           // Résout page:home/index
    protected string $componentNs = 'home';      // Résout component:home/actualites
    protected string $layout = 'main';           // Layout public par défaut

    public function __construct(
        private HomeService $homeService,
        private ArticleService $articleService,
        ResponseFactoryInterface $res,
        ViewRendererInterface $view
    ) {
        parent::__construct($res, $view);
    }

    #[Route(path: '/', methods: ['GET'])]
    public function home(): Response
    {
        // 1) Inputs (query) — pagination standardisée
        $page = Paginator::fromGlobals(defaultLimit: 12, maxLimit: 100);

        // 2) Domaine — agrégation via HomeService
        $dto = $this->homeService->getHomeData($page);

        // 3) Présentation — projection DOMAINE -> VUE
        $viewData = HomePresenter::forView($dto);

        // 4) UI annexes (i18n/lang, csrf, auth)
        $i18n = $this->i18n();
        $currentLang = $i18n['lang'] ?? 'fr';
        $languages = LanguageOptionsProvider::make($i18n, $currentLang);

        // 5) Rendu avec namespace automatique
        return $this->page('index', [  // ✅ Résout vers page:home/index
            'showHeader' => true,
            'showFooter' => true,
            'str' => $i18n,
            'csrf_input' => $this->csrfInput(),
            'action' => '/home/generate_ics',
            'isAuthenticated' => $this->isAuthenticated(),
            'languages' => $languages,
            'contact_action' => '/contact',
            'pagination' => ['page' => $page->page, 'limit' => $page->limit],
        ] + $viewData);
    }

    #[Route(path: '/projet', methods: ['GET'])]
    public function projet(): Response
    {
        // ✅ Résout vers page:home/projet (ou créer page:projet/index si préféré)
        return $this->page('projet:index', [  // Override namespace pour ce cas
            'showHeader' => true,
            'showFooter' => true,
            'str' => $this->i18n(),
        ]);
    }

    #[Route(path: '/galerie', methods: ['GET'])]
    public function galerie(): Response
    {
        // ✅ Résout vers page:galerie/index
        return $this->page('galerie:index', [
            'showHeader' => true,
            'showFooter' => true,
            'str' => $this->i18n(),
        ]);
    }

    #[Route(path: '/article/{id}', methods: ['GET'])]
    public function article(int $id): Response
    {
        $dto = $this->articleService->getById($id);

        // Projection minimale
        $article = [
            'id' => (int)($dto->id ?? $id),
            'title' => (string)($dto->titre ?? ''),
            'summary' => (string)($dto->resume ?? ''),
            'date' => (string)($dto->date_article ?? ''),
            'time' => substr((string)($dto->hours ?? ''), 0, 5),
            'place' => (string)($dto->lieu ?? ''),
            'author' => isset($dto->author) ? (string)$dto->author : '',
            'description' => isset($dto->description) ? (string)$dto->description : '',
            'image' => isset($dto->image) ? Safe::imageUrl((string)$dto->image) : '',
        ];

        // ✅ Résout vers page:article/detail
        return $this->page('article:detail', [
            'showHeader' => true,
            'showFooter' => true,
            'str' => $this->i18n(),
            'article' => $article,
        ]);
    }
}
