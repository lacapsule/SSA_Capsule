<?php

declare(strict_types=1);

namespace App\Modules\Agenda;

use Capsule\Contracts\ResponseFactoryInterface;
use Capsule\Http\Message\Request;
use Capsule\Http\Message\Response;
use Capsule\Routing\Attribute\Route;
use Capsule\Routing\Attribute\RoutePrefix;
use App\Modules\Agenda\Dto\AgendaEventDTO;

/**
 * Contrôleur public pour l'agenda.
 *
 * Expose une API en lecture seule pour le calendrier public
 * sans passer par le préfixe `/dashboard` (et donc sans
 * être bloqué par le middleware d'authentification).
 */
#[RoutePrefix('/agenda')]
final class PublicAgendaController
{
    public function __construct(
        private AgendaService $agenda,
        private ResponseFactoryInterface $res,
    ) {
    }

    /**
     * GET /agenda/api/events
     * Retourne les événements sur une période donnée au format JSON.
     *
     * Accessible sans authentification.
     */
    #[Route(path: '/api/events', methods: ['GET'])]
    public function getEventsPublic(Request $req): Response
    {
        $start = $this->strFromQuery($req, 'start');
        $end = $this->strFromQuery($req, 'end');

        if ($start === null || $end === null) {
            return $this->res->json(['error' => 'Les paramètres start et end sont requis.'], 400);
        }

        $events = $this->agenda->getEvents($start, $end);

        $formattedEvents = array_map(
            static function (AgendaEventDTO $event): array {
                return [
                    'id' => $event->id,
                    'title' => $event->title,
                    'start' => $event->startsAt->format('Y-m-d H:i:s'),
                    'end' => $event->endsAt()->format('Y-m-d H:i:s'),
                    'description' => $event->location,
                    'color' => $event->color,
                    'all_day' => false,
                ];
            },
            $events
        );

        return $this->res->json($formattedEvents);
    }

    /**
     * Helper interne pour extraire proprement une string de la query.
     */
    private function strFromQuery(Request $req, string $key): ?string
    {
        $value = $req->query[$key] ?? null;

        return \is_string($value) && $value !== '' ? $value : null;
    }
}



