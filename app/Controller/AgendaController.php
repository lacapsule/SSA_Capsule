<?php

declare(strict_types=1);

namespace App\Controller;

use App\Provider\SidebarLinksProvider;
use App\Service\AgendaService;
use App\View\Presenter\AgendaPresenter;
use Capsule\Contracts\ResponseFactoryInterface;
use Capsule\Contracts\ViewRendererInterface;
use Capsule\Http\Message\Request;
use Capsule\Http\Message\Response;
use Capsule\Routing\Attribute\Route;
use Capsule\Routing\Attribute\RoutePrefix;
use Capsule\Security\CsrfTokenManager;
use Capsule\View\BaseController;
use DateTime;

#[RoutePrefix('/dashboard/agenda')]
final class AgendaController extends BaseController
{
    public function __construct(
        private AgendaService $agenda,
        private readonly SidebarLinksProvider $linksProvider,
        ResponseFactoryInterface $res,
        ViewRendererInterface $view,
    ) {
        parent::__construct($res, $view);
    }

    /** GET /dashboard/agenda */
    #[Route(path: '', methods: ['GET'])]
    public function index(Request $req): Response
    {
        $monday = $this->mondayFromQuery($req);
        $events = $this->agenda->getWeekEvents($monday);

        $agendaData = AgendaPresenter::index(
            base: ['csrfInput' => $this->csrfInput()],
            events: $events,
            monday: $monday
        );

        $data = array_merge(
            $this->dashboardBase('Mon Agenda'),
            $agendaData,
            ['component' => 'dashboard/dash_agenda']
        );

        return $this->page('dashboard:home', $data);
    }

    /** POST /dashboard/agenda/create */
    #[Route(path: '/create', methods: ['POST'])]
    public function create(): Response
    {
        CsrfTokenManager::requireValidToken();

        $title = trim((string)($_POST['titre'] ?? ''));
        $date = (string)($_POST['date'] ?? '');
        $time = (string)($_POST['heure'] ?? '');
        $loc = trim((string)($_POST['lieu'] ?? ''));
        $durH = (float)($_POST['duree'] ?? 1.0);

        [$ok, $errors, $startsAt] = $this->agenda->create(
            title: $title,
            dateYmd: $date,
            timeHi: $time,
            location: $loc !== '' ? $loc : null,
            durationHours: $durH,
            createdBy: $this->currentUser()['id'] ?? null
        );

        if (!$ok) {
            return $this->redirectWithErrors(
                '/dashboard/agenda',
                'Le formulaire contient des erreurs.',
                $errors,
                ['titre' => $title, 'date' => $date, 'heure' => $time, 'lieu' => $loc, 'duree' => $durH]
            );
        }

        $monday = $this->agenda->mondayOf($startsAt ?? new DateTime());

        return $this->res->redirect(
            '/dashboard/agenda?week=' . rawurlencode($monday->format('d-m-Y')),
            303
        );
    }

    /* ======= Helpers ======= */

    private function dashboardBase(string $pageTitle): array
    {
        return [
            'title' => $pageTitle,
            'showHeader' => false,
            'i18n' => $this->i18n(),
            'links' => $this->linksProvider->get($this->isAdmin()),
        ];
    }

    private function mondayFromQuery(Request $req): DateTime
    {
        $weekParam = $this->strFromQuery($req, 'week');

        $base = $weekParam !== null
            ? (DateTime::createFromFormat('d-m-Y', $weekParam) ?: new DateTime())
            : new DateTime();

        return $this->agenda->mondayOf($base)->setTime(0, 0);
    }

    private function strFromQuery(Request $req, string $key): ?string
    {
        $value = $req->query[$key] ?? null;

        return is_string($value) && $value !== '' ? $value : null;
    }
}
