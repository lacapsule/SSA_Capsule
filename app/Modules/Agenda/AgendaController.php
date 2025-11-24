<?php

declare(strict_types=1);

namespace App\Modules\Agenda;

use Capsule\Contracts\ResponseFactoryInterface;
use Capsule\Contracts\ViewRendererInterface;
use Capsule\Http\Message\Request;
use Capsule\Http\Message\Response;
use Capsule\Routing\Attribute\Route;
use Capsule\Routing\Attribute\RoutePrefix;
use Capsule\Security\CsrfTokenManager;
use Capsule\View\BaseController;
use App\Providers\SidebarLinksProvider;
use DateTime;
use App\Modules\Agenda\Dto\AgendaEventDTO;

#[RoutePrefix('/dashboard/agenda')]
final class AgendaController extends BaseController
{
    //  Configuration du module Agenda
    protected string $pageNs = 'dashboard';
    protected string $componentNs = 'dashboard';
    protected string $layout = 'dashboard';  // Layout dashboard avec sidebar

    public function __construct(
        private AgendaService $agenda,
        private readonly SidebarLinksProvider $linksProvider,
        ResponseFactoryInterface $res,
        ViewRendererInterface $view,
    ) {
        parent::__construct($res, $view);
    }

    /**
     * Shell commun dashboard
     * @return array<string,mixed>
     */
    private function dashboardBase(string $pageTitle): array
    {
        return [
            'title' => $pageTitle,
            'str' => $this->i18n(),
            'user' => $this->currentUser(),
            'isAdmin' => $this->isAdmin(),
            'links' => $this->linksProvider->get($this->isAdmin()),
        ];
    }

    /**
     * GET /dashboard/agenda
     * Affiche l'agenda de la semaine
     */
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
            ['component' => 'dashboard/components/agenda']  // ✅ Chemin corrigé
        );

        return $this->page('index', $data);
    }

    /**
     * POST /dashboard/agenda/create
     * Crée un nouvel événement
     */
    #[Route(path: '/create', methods: ['POST'])]
    public function create(): Response
    {
        CsrfTokenManager::requireValidToken();

        $title = trim((string) ($_POST['titre'] ?? ''));
        $date = (string) ($_POST['date'] ?? '');
        $time = (string) ($_POST['heure'] ?? '');
        $loc = trim((string) ($_POST['lieu'] ?? ''));
        $durH = (float) ($_POST['duree'] ?? 1.0);

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
                [
                    'titre' => $title,
                    'date' => $date,
                    'heure' => $time,
                    'lieu' => $loc,
                    'duree' => $durH
                ]
            );
        }

        // Redirection vers la semaine de l'événement créé
        $monday = $this->agenda->mondayOf($startsAt ?? new DateTime());

        return $this->redirectWithSuccess(
            '/dashboard/agenda?week=' . rawurlencode($monday->format('d-m-Y')),
            'Événement créé avec succès.'
        );
    }

    // /**
    //  * POST /dashboard/agenda/update/{id}
    //  * Met à jour un événement existant
    //  */
    // #[Route(path: '/update/{id}', methods: ['POST'])]
    // public function update(int $id): Response
    // {
    //     CsrfTokenManager::requireValidToken();
    //
    //     $title = trim((string)($_POST['titre'] ?? ''));
    //     $date = (string)($_POST['date'] ?? '');
    //     $time = (string)($_POST['heure'] ?? '');
    //     $loc = trim((string)($_POST['lieu'] ?? ''));
    //     $durH = (float)($_POST['duree'] ?? 1.0);
    //
    //     [$ok, $errors] = $this->agenda->update(
    //         id: $id,
    //         title: $title,
    //         dateYmd: $date,
    //         timeHi: $time,
    //         location: $loc !== '' ? $loc : null,
    //         durationHours: $durH
    //     );
    //
    //     if (!$ok) {
    //         return $this->redirectWithErrors(
    //             '/dashboard/agenda',
    //             'Erreur lors de la modification.',
    //             $errors,
    //             [
    //                 'titre' => $title,
    //                 'date' => $date,
    //                 'heure' => $time,
    //                 'lieu' => $loc,
    //                 'duree' => $durH
    //             ]
    //         );
    //     }
    //
    //     return $this->redirectWithSuccess(
    //         '/dashboard/agenda',
    //         'Événement modifié avec succès.'
    //     );
    // }

    /**
     * GET /dashboard/agenda/api/events
     * Récupère les événements pour une période donnée au format JSON
     */
    #[Route(path: '/api/events', methods: ['GET'])]
    public function getEventsApi(Request $req): Response
    {
        $start = $this->strFromQuery($req, 'start');
        $end = $this->strFromQuery($req, 'end');

        if ($start === null || $end === null) {
            return $this->res->json(['error' => 'Les paramètres start et end sont requis.'], 400);
        }

        $events = $this->agenda->getEvents($start, $end);

        $formattedEvents = array_map(function (AgendaEventDTO $event) {
            return [
                'id' => $event->id,
                'title' => $event->title,
                'start' => $event->startsAt->format('Y-m-d H:i:s'),
                'end' => $event->endsAt()->format('Y-m-d H:i:s'),
                'description' => $event->location,
                'color' => $event->color, // Couleur par défaut
                'all_day' => false,
            ];
        }, $events);

        return $this->res->json($formattedEvents);
    }

    /**
     * POST /dashboard/agenda/api/delete/{id}
     * Supprime un événement via API
     */
    #[Route(path: '/api/delete/{id}', methods: ['POST'])]
    public function deleteApi(int $id): Response
    {
        CsrfTokenManager::requireValidToken();

        $ok = $this->agenda->delete($id);

        if (!$ok) {
            return $this->res->json(['success' => false, 'message' => 'Événement non trouvé.'], 404);
        }

        return $this->res->json(['success' => true]);
    }

    /**
     * POST /dashboard/agenda/api/create
     * Crée un événement via API
     */
    #[Route(path: '/api/create', methods: ['POST'])]
    public function createApi(): Response
    {
        CsrfTokenManager::requireValidToken();

        $title = trim((string) ($_POST['title'] ?? ''));
        $startStr = (string) ($_POST['start'] ?? '');
        $endStr = (string) ($_POST['end'] ?? '');
        $description = trim((string) ($_POST['description'] ?? ''));
        $color = (string) ($_POST['color'] ?? '#3788d8');

        if (empty($title) || empty($startStr) || empty($endStr)) {
            return $this->res->json(['success' => false, 'message' => 'Champs requis manquants.'], 400);
        }

        try {
            $start = new DateTime($startStr);
            $end = new DateTime($endStr);
        } catch (\Exception $e) {
            return $this->res->json(['success' => false, 'message' => 'Format de date invalide.'], 400);
        }

        if ($start >= $end) {
            return $this->res->json(['success' => false, 'message' => 'La date de fin doit être après la date de début.'], 400);
        }

        $start = new DateTime($startStr);
        $end = new DateTime($endStr);
        $dateYmd = $start->format('Y-m-d');
        $timeHi = $start->format('H:i');
        $durationSeconds = $end->getTimestamp() - $start->getTimestamp();
        $durationHours = $durationSeconds > 0 ? $durationSeconds / 3600 : 0.25;

        [$ok, $errors] = $this->agenda->create(
            title: $title,
            dateYmd: $dateYmd,
            timeHi: $timeHi,
            location: $description,
            durationHours: $durationHours,
            createdBy: $this->currentUser()['id'] ?? null,
            color: $color
        );

        if (!$ok) {
            return $this->res->json(['success' => false, 'errors' => $errors], 400);
        }

        return $this->res->json(['success' => true]);
    }

    /**
     * POST /dashboard/agenda/api/update/{id}
     * Met à jour un événement via API
     */
    #[Route(path: '/api/update/{id}', methods: ['POST'])]
    public function updateApi(int $id): Response
    {
        CsrfTokenManager::requireValidToken();

        $title = trim((string) ($_POST['title'] ?? ''));
        $startStr = (string) ($_POST['start'] ?? '');
        $endStr = (string) ($_POST['end'] ?? '');
        $description = trim((string) ($_POST['description'] ?? ''));
        $color = (string) ($_POST['color'] ?? '#3788d8');

        [$ok, $errors] = $this->agenda->update($id, $title, $startStr, $endStr, $description, $color);

        if (!$ok) {
            return $this->res->json(['success' => false, 'errors' => $errors], 400);
        }

        return $this->res->json(['success' => true]);
    }

    /**
     * POST /dashboard/agenda/delete/{id}
     * Supprime un événement
     */
    #[Route(path: '/delete/{id}', methods: ['POST'])]
    public function delete(int $id): Response
    {
        CsrfTokenManager::requireValidToken();

        $this->agenda->delete($id);

        return $this->redirectWithSuccess(
            '/dashboard/agenda',
            'Événement supprimé avec succès.'
        );
    }

    /* ======= Helpers ======= */

    /**
     * Extrait le lundi de la semaine depuis la query string
     */
    private function mondayFromQuery(Request $req): DateTime
    {
        $weekParam = $this->strFromQuery($req, 'week');
        $base = $weekParam !== null
            ? (DateTime::createFromFormat('d-m-Y', $weekParam) ?: new DateTime())
            : new DateTime();

        return $this->agenda->mondayOf($base)->setTime(0, 0);
    }

    /**
     * Récupère une valeur string depuis la query
     */
    private function strFromQuery(Request $req, string $key): ?string
    {
        $value = $req->query[$key] ?? null;

        return is_string($value) && $value !== '' ? $value : null;
    }
}
