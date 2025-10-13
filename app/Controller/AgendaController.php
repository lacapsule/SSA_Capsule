<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\AgendaService;
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
    private const PX_PER_HOUR = 64;

    public function __construct(
        private AgendaService $agenda,
        ResponseFactoryInterface $res,
        ViewRendererInterface $view,
    ) {
        parent::__construct($res, $view);
    }

    /** GET /dashboard/agenda */
    #[Route(path: '', methods: ['GET'])]
    public function index(Request $req): Response
    {
        [$monday, $days] = $this->computeWeek($this->strFromQuery($req, 'week'));
        $hours = $this->hoursRange(8, 18);

        $weekLabel = $monday->format('d-m-Y');
        $prevMonday = (clone $monday)->modify('-7 days')->format('d-m-Y');
        $nextMonday = (clone $monday)->modify('+7 days')->format('d-m-Y');

        // 1) Events de la semaine via le service
        $events = $this->agenda->getWeekEvents($monday);

        $i18n = $this->i18n();

        // 2) Grille pour la vue
        $grid = $this->buildGrid($days, $hours, $events);

        // 3) Rendu (shell dashboard)
        return $this->page('dashboard:home', [
            'title' => 'Mon agenda',
            'isDashboard' => true,
            'showHeader' => false,
            'showFooter' => false,
            'str' => $i18n,
            'component' => 'dashboard/dash_agenda',
            'create_url' => '/dashboard/agenda/create',
            'csrf_input' => $this->csrfInput(),
            'week_label' => $weekLabel,
            'prev_week_url' => '/dashboard/agenda?week=' . rawurlencode($prevMonday),
            'next_week_url' => '/dashboard/agenda?week=' . rawurlencode($nextMonday),
            'days' => $grid['days'],
            'hours' => $grid['hours'],
            'modal_open' => false,
        ]);
    }

    /** POST /dashboard/agenda/create */
    #[Route(path: '/create', methods: ['POST'])]
    public function create(): Response
    {
        CsrfTokenManager::requireValidToken();

        $title = (string)($_POST['titre'] ?? '');
        $date = (string)($_POST['date'] ?? '');   // YYYY-MM-DD
        $time = (string)($_POST['heure'] ?? '');  // HH:MM
        $loc = (string)($_POST['lieu'] ?? '');
        $durH = (float)($_POST['duree'] ?? 1.0);

        [$ok, $errors, $startsAt] = $this->agenda->create(
            title: $title,
            dateYmd: $date,
            timeHi: $time,
            location: $loc,
            durationHours: $durH,
            createdBy: ($this->currentUser()['id'] ?? null)
        );

        if (!$ok) {
            return $this->redirectWithErrors(
                '/dashboard/agenda',
                'Le formulaire contient des erreurs.',
                $errors,
                ['titre' => $title, 'date' => $date, 'heure' => $time, 'lieu' => $loc, 'duree' => $durH]
            );
        }

        // Redirige sur la semaine de l’event (PRG)
        $monday = $this->agenda->mondayOf($startsAt ?? new DateTime())->format('d-m-Y');

        return $this->res->redirect('/dashboard/agenda?week=' . rawurlencode($monday), 303);
    }

    /* ==================== Helpers (inchangés) ==================== */

    private function strFromQuery(Request $req, string $key): ?string
    {
        $q = $req->query[$key] ?? null;

        return is_string($q) ? $q : null;
    }

    /**
     * @return array{0:DateTime,1:list<array{name:string,date:string}>}
     */
    private function computeWeek(?string $weekParam): array
    {
        $base = $weekParam
            ? DateTime::createFromFormat('d-m-Y', $weekParam) ?: new DateTime()
            : new DateTime();

        $monday = $this->agenda->mondayOf($base);
        $labels = ['Lundi','Mardi','Mercredi','Jeudi','Vendredi','Samedi','Dimanche'];
        $days = [];
        foreach ($labels as $i => $name) {
            $d = (clone $monday)->modify("+{$i} days");
            $days[] = ['name' => $name, 'date' => $d->format('d-m-Y')];
        }

        return [$monday, $days];
    }

    /** @return list<array{display:string,hour:int}> */
    private function hoursRange(int $start, int $end): array
    {
        $out = [];
        foreach (range($start, $end) as $h) {
            $out[] = ['display' => sprintf('%02d:00', $h), 'hour' => $h];
        }

        return $out;
    }

    /**
     * @param list<array{name:string,date:string}> $days
     * @param list<array{display:string,hour:int}> $hours
     * @param list<array{date:string,time:string,title:string,location:string,duration:float}> $events
     * @return array{days:list<array{name:string,date:string}>,hours:list<array{display:string,hour:int,days:list<array{date:string,name:string,has_events:bool,events:list<array{title:string,date:string,time:string,location:string,duration:float,css_class:string,height_px:int,top_px:int}>}>}>}
     */
    private function buildGrid(array $days, array $hours, array $events): array
    {
        $index = [];
        foreach ($events as $e) {
            $hour = (int)substr($e['time'], 0, 2);
            $index[$e['date']][$hour][] = $e;
        }

        $pxHour = self::PX_PER_HOUR;

        $hoursForTpl = array_map(function ($h) use ($days, $index, $pxHour) {
            $perDay = array_map(function ($d) use ($h, $index, $pxHour) {
                $hour = (int)$h['hour'];
                $items = $index[$d['date']][$hour] ?? [];

                $cellEvents = [];
                foreach ($items as $ev) {
                    $isHalf = (substr($ev['time'], 3, 2) === '30');
                    $cellEvents[] = [
                        'title' => $ev['title'],
                        'date' => $ev['date'],
                        'time' => $ev['time'],
                        'location' => $ev['location'],
                        'duration' => $ev['duration'],
                        'css_class' => $isHalf ? 'event-half-hour' : 'event-whole-hour',
                        'height_px' => (int)round($ev['duration'] * $pxHour),
                        'top_px' => $isHalf ? (int)($pxHour / 2) : 0,
                    ];
                }

                return [
                    'name' => $d['name'],
                    'date' => $d['date'],
                    'has_events' => !empty($cellEvents),
                    'events' => $cellEvents,
                ];
            }, $days);

            return [
                'display' => $h['display'],
                'hour' => $h['hour'],
                'days' => $perDay,
            ];
        }, $hours);

        return [
            'days' => $days,
            'hours' => $hoursForTpl,
        ];
    }
}
