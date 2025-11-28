<?php

declare(strict_types=1);

namespace App\Modules\Home;

use App\Modules\Article\ArticleService;
use App\Modules\Home\ContactService;
use App\Modules\Agenda\AgendaService;
use App\Modules\Agenda\Dto\AgendaEventDTO;
use App\Providers\LanguageOptionsProvider;
use Capsule\Support\Pagination\Page;
use Capsule\Support\Pagination\Paginator;
use Capsule\Contracts\ResponseFactoryInterface;
use Capsule\Contracts\ViewRendererInterface;
use Capsule\Routing\Attribute\Route;
use Capsule\Security\CsrfTokenManager;
use Capsule\View\BaseController;
use Capsule\View\Safe;
use Capsule\Http\Message\Request;
use Capsule\Http\Message\Response;

final class HomeController extends BaseController
{
    // Configuration du module Home
    protected string $pageNs = 'home';           // Résout page:home/index
    protected string $componentNs = 'home';      // Résout component:home/actualites
    protected string $layout = 'main';           // Layout public par défaut

    public function __construct(
        private HomeService $homeService,
        private ArticleService $articleService,
        private ContactService $contactService,
        private AgendaService $agendaService,
        ResponseFactoryInterface $res,
        ViewRendererInterface $view
    ) {
        parent::__construct($res, $view);
    }

    #[Route(path: '/', methods: ['GET'])]
    public function home(): Response
    {
        // 1) Inputs (query) — pagination standardisée
        $paginator = Paginator::fromGlobals(defaultLimit: 3, maxLimit: 100);
        $totalArticles = $this->articleService->countAll();
        $page = new Page(
            page: $paginator->page,
            limit: $paginator->limit,
            total: $totalArticles
        );

        // 2) Domaine — agrégation via HomeService
        $dto = $this->homeService->getHomeData($page);

        // 3) Présentation — projection DOMAINE -> VUE
        $viewData = HomePresenter::forView($dto);

        $flash = $this->flashMessages();
        $formErrors = $this->formErrors();
        $formData = $this->formData();

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
            'contact_errors' => $formErrors,
            'contact_old' => $formData,
            'flash_success' => $flash['success'] ?? [],
            'flash_error' => $flash['error'] ?? [],
        ] + $viewData);
    }

    #[Route(path: '/contact', methods: ['POST'])]
    public function contactSubmit(): Response
    {
        CsrfTokenManager::requireValidToken();

        $input = [
            'name' => (string)($_POST['name'] ?? ''),
            'email' => (string)($_POST['email'] ?? ''),
            'subject' => (string)($_POST['subject'] ?? ''),
            'message' => (string)($_POST['message'] ?? ''),
            'honeypot' => (string)($_POST['website'] ?? ''),
            'bot_check' => (string)($_POST['confirm_robot'] ?? ''),
        ];

        $result = $this->contactService->handle($input, $this->clientIp());

        if (isset($result['errors'])) {
            return $this->redirectWithErrors(
                '/#contact',
                'Merci de vérifier le formulaire de contact.',
                $result['errors'],
                $result['data'] ?? []
            );
        }

        return $this->redirectWithSuccess(
            '/#contact',
            'Merci ! Votre message a bien été envoyé.'
        );
    }

    /**
     * GET /api/events
     * API publique pour récupérer les événements du calendrier
     */
    #[Route(path: '/api/events', methods: ['GET'])]
    public function getEventsApi(Request $req): Response
    {
        $start = $this->strFromQuery($req, 'start');
        $end = $this->strFromQuery($req, 'end');

        if ($start === null || $end === null) {
            return $this->res->json(['error' => 'Les paramètres start et end sont requis.'], 400);
        }

        $events = $this->agendaService->getEvents($start, $end);

        $formattedEvents = array_map(function (AgendaEventDTO $event) {
            return [
                'id' => $event->id,
                'title' => $event->title,
                'start' => $event->startsAt->format('Y-m-d H:i:s'),
                'end' => $event->endsAt()->format('Y-m-d H:i:s'),
                'description' => $event->location,
                'color' => $event->color,
                'all_day' => false,
            ];
        }, $events);

        return $this->res->json($formattedEvents);
    }

    /**
     * Récupère une valeur string depuis la query
     */
    private function strFromQuery(Request $req, string $key): ?string
    {
        $value = $req->query[$key] ?? null;
        return is_string($value) && $value !== '' ? $value : null;
    }

    #[Route(path: '/article/{id}', methods: ['GET'])]
    public function article(int $id): Response
    {
        $dto = $this->articleService->getById($id);
        if ($dto === null) {
            return $this->res->text('Not Found', 404);
        }

        // Projection minimale
        $mediaPaths = $dto?->images ?? [];
        if ($mediaPaths === [] && ($dto?->image)) {
            $mediaPaths = [(string) $dto->image];
        }

        $normalizedPaths = array_map(
            static fn (string $path): string => self::normalizeMediaPath($path),
            $mediaPaths
        );

        // Prépare une structure riche pour le carousel (images + vidéos)
        $mediaItems = array_map(
            static function (string $path): array {
                $isVideo = self::isVideoPath($path);

                return [
                    'src' => (string) Safe::imageUrl($path),
                    'isVideo' => $isVideo,
                    'isImage' => !$isVideo,
                ];
            },
            $normalizedPaths
        );

        // Cover = première image (de préférence), sinon fallback miniature unique
        $firstImage = null;
        foreach ($mediaItems as $item) {
            if (!empty($item['isImage'])) {
                $firstImage = $item['src'];
                break;
            }
        }

        $coverSrc = $firstImage
            ?? ($dto?->image ? (string) Safe::imageUrl(self::normalizeMediaPath((string) $dto->image)) : '');
        $article = [
            'id' => (int) $dto->id,
            'title' => (string) $dto->titre,
            'summary' => (string) $dto->resume,
            'date' => (string) $dto->date_article,
            'time' => substr((string) $dto->hours, 0, 5),
            'place' => (string) ($dto->lieu ?? ''),
            'author' => isset($dto->author) ? (string) $dto->author : '',
            'description' => isset($dto->description) ? (string) $dto->description : '',
            'image' => $coverSrc,
        ];
        $hasCarousel = count($mediaItems) > 1;

        // ✅ Résout vers page:article/articleDetails
        return $this->page('page:article/articleDetails', [
            'showHeader' => true,
            'showFooter' => true,
            'str' => $this->i18n(),
            'isAuthenticated' => $this->isAuthenticated(),
            'article' => $article,
            'medias' => $mediaItems,
            'mediaCover' => $coverSrc,
            'hasCarousel' => $hasCarousel,
        ]);
    }
    private static function isVideoPath(string $path): bool
    {
        $target = parse_url($path, PHP_URL_PATH) ?: $path;
        $extension = strtolower(pathinfo((string) $target, PATHINFO_EXTENSION));

        return in_array($extension, ['mp4', 'webm', 'ogv', 'ogg', 'mov'], true);
    }

    private static function normalizeMediaPath(string $path): string
    {
        $path = trim($path);
        if ($path === '') {
            return '';
        }

        if (str_starts_with($path, '/assets/')) {
            return $path;
        }

        $withoutLeadingPublic = preg_replace('#^/?public/#', '/', $path) ?? $path;
        if (str_starts_with($withoutLeadingPublic, '/assets/')) {
            return $withoutLeadingPublic;
        }

        if (str_starts_with($path, 'assets/')) {
            return '/' . ltrim($path, '/');
        }

        $pos = strpos($path, '/assets/');
        if ($pos !== false) {
            return substr($path, $pos);
        }

        return $path;
    }
    private function clientIp(): string
    {
        foreach (['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'] as $key) {
            if (!isset($_SERVER[$key]) || $_SERVER[$key] === '') {
                continue;
            }

            $value = explode(',', (string)$_SERVER[$key])[0];
            $value = trim($value);

            if ($value !== '') {
                return $value;
            }
        }

        return 'unknown';
    }
}
