<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\ArticleService;
use Capsule\Contracts\ResponseFactoryInterface;
use Capsule\Contracts\ViewRendererInterface;
use Capsule\Http\Message\Response;
use Capsule\Routing\Attribute\Route;
use Capsule\View\BaseController;

final class HomeController extends BaseController
{
    public function __construct(
        private ArticleService $articleService,
        ResponseFactoryInterface $res,
        ViewRendererInterface $view
    ) {
        parent::__construct($res, $view);
    }

    /**
     * @param null|array<object>|\Traversable<object> $raw
     * @return list<array{date:string,time:string,title:string,summary:string,location:string,ics_datetime:string}>
     */
    private function mapArticles(null|array|\Traversable $raw): array
    {
        if ($raw === null) {
            return [];
        }
        $items = is_array($raw) ? $raw : iterator_to_array($raw);

        return array_map(function ($a) {
            $date = (string)($a->date_article ?? '');
            $time = substr((string)($a->hours ?? ''), 0, 5);

            return [
                'date' => $date,
                'time' => $time,
                'title' => (string)($a->titre ?? ''),
                'summary' => (string)($a->resume ?? ''),
                'location' => (string)($a->lieu ?? ''),
                'ics_datetime' => $date . ' ' . $time . ':00',
            ];
        }, $items);
    }

    #[Route(path: '/', methods: ['GET'])]
    public function home(): Response
    {
        // Agenda
        $articles = $this->mapArticles($this->articleService->getUpcoming());

        // CSRF (trusted HTML)
        $csrf_input = $this->csrfInput();

        $currentLang = $_SESSION['lang'] ?? 'fr';
        $isAuth = $this->isAuthenticated();
        $i18n = $this->translations();
        $languages = [
            ['code' => 'fr','label' => $i18n['lang_fr'] ?? 'Français','selected' => $currentLang === 'fr'],
            ['code' => 'br','label' => $i18n['lang_br'] ?? 'Brezhoneg','selected' => $currentLang === 'br'],
        ];

        // Partenaires / financeurs
        $all = [
            ['name' => 'BUZUK', 'role' => 'partenaire', 'url' => 'https://buzuk.bzh/', 'logo' => '/assets/img/buzuk.webp'],
            ['name' => 'Région Bretagne', 'role' => 'financeur', 'url' => 'https://www.bretagne.bzh/', 'logo' => '/assets/img/bretagne.webp'],
            ['name' => 'ULAMIR-CPIE', 'role' => 'partenaire', 'url' => 'https://ulamir-cpie.bzh/', 'logo' => '/assets/img/ulamircpie.webp'],
            ['name' => 'Pôle ESS Pays de Morlaix', 'role' => 'partenaire', 'url' => 'https://www.adess29.fr/faire-reseau/le-pole-du-pays-de-morlaix/', 'logo' => '/assets/img/ess.webp'],
            ['name' => 'RESAM', 'role' => 'partenaire', 'url' => 'https://www.resam.net/', 'logo' => '/assets/img/resam.webp'],
            ['name' => 'Leader financement Européen', 'role' => 'financeur', 'url' => 'https://leaderfrance.fr/le-programme-leader/', 'logo' => '/assets/img/feader.webp'],
        ];
        $partenaires = array_values(array_filter($all, fn ($p) => $p['role'] === 'partenaire'));
        $financeurs = array_values(array_filter($all, fn ($p) => $p['role'] === 'financeur'));

        // -> page('home') => 'page:home' -> templates/pages/home.tpl.php (layout appliqué par ViewRenderer)
        return $this->page('home', [
            'showHeader' => true,
            'showFooter' => true,
            'str' => $i18n,
            'articles' => $articles,
            'csrf_input' => $csrf_input,         // {{{csrf_input}}}
            'action' => '/home/generate_ics',
            'isAuthenticated' => $isAuth,
            'languages' => $languages,
            'partenaires' => $partenaires,
            'financeurs' => $financeurs,
            'contact_action' => '/contact',
        ]);
    }

    #[Route(path: '/projet', methods: ['GET'])]
    public function projet(): Response
    {
        return $this->page('projet', [
            'showHeader' => true,
            'showFooter' => true,
            'str' => $this->translations(),
        ]);
    }

    #[Route(path: '/galerie', methods: ['GET'])]
    public function galerie(): Response
    {
        return $this->page('galerie', [
            'showHeader' => true,
            'showFooter' => true,
            'str' => $this->translations(),
        ]);
    }

    #[Route(path: '/article/{id}', methods: ['GET'])]
    public function article(int $id): Response
    {
        if ($id <= 0) {
            return $this->res->text('Bad Request', 400);
        }

        $dto = $this->articleService->getById($id);
        if (!$dto) {
            return $this->res->text('Not Found', 404);
        }

        return $this->page('articleDetails', [
            'showHeader' => true,
            'showFooter' => true,
            'str' => $this->translations(),
            'article' => [
                'title' => (string)($dto->titre ?? ''),
                'summary' => (string)($dto->resume ?? ''),
                'date' => (string)($dto->date_article ?? ''),
                'time' => substr((string)($dto->hours ?? ''), 0, 5),
                'place' => (string)($dto->lieu ?? ''),
            ],
        ]);
    }
}
