<?php

declare(strict_types=1);

namespace App\Modules\Agenda;

use DateTime;

final class AgendaPresenter
{
    private const DAYS_FR = ['LUNDI', 'MARDI', 'MERCREDI', 'JEUDI', 'VENDREDI', 'SAMEDI', 'DIMANCHE'];
    private const MONTHS_FR = [
        1 => 'JAN', 'FEV', 'MAR', 'AVR', 'MAI', 'JUIN',
        'JUIL', 'AOUT', 'SEPT', 'OCT', 'NOV', 'DEC'
    ];

    /**
     * @param array<int,mixed> $base
     * @param array<int,mixed> $events
     * @return array<int|string,mixed>
     */
    public static function index(array $base, array $events, DateTime $monday): array
    {
        $prevMonday = (clone $monday)->modify('-7 days');
        $nextMonday = (clone $monday)->modify('+7 days');

        // ✅ Ajouter les dates complètes de chaque jour pour le JavaScript
        $weekDates = [];
        for ($i = 0; $i < 7; $i++) {
            $date = (clone $monday)->modify("+{$i} days");
            $weekDates[] = [
                'name' => self::DAYS_FR[$i],
                'date' => $date->format('d/m'),
                'iso' => $date->format('Y-m-d'), // ✅ Format ISO pour comparaison JS
            ];
        }

        // Formatter les événements
        $flatEvents = [];
        foreach ($events as $e) {
            $dayOfWeek = self::DAYS_FR[(int) $e->startsAt->format('N') - 1];
            $day = (int) $e->startsAt->format('d');
            $flatEvents[] = [
                'id' => $e->id,
                'title' => $e->title,
                'date' => sprintf('%s %02d', $dayOfWeek, $day),
                'time' => $e->startsAt->format('H:i'),
                'location' => $e->location ?? '',
                'duration' => round($e->durationMinutes / 60, 1),
            ];
        }

        return array_merge($base, [
            'week_label' => $monday->format('d/m/Y'),
            'monday_iso' => $monday->format('Y-m-d'), // ✅ Pour le JS
            'prev_week_url' => '/dashboard/agenda?week=' . $prevMonday->format('d-m-Y'),
            'next_week_url' => '/dashboard/agenda?week=' . $nextMonday->format('d-m-Y'),
            'create_url' => '/dashboard/agenda/create',
            'week_dates' => $weekDates, // ✅ Dates de la semaine
            'events' => $flatEvents,
            'events_count' => count($flatEvents),
        ]);
    }
}
