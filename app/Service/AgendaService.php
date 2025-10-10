<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\AgendaRepository;
use DateTime;

final class AgendaService
{
    public function __construct(private AgendaRepository $repo)
    {
    }

    /**
     * @return list<array{date:string,time:string,title:string,location:string,duration:float}>
     */
    public function getWeekEvents(DateTime $monday): array
    {
        [$start, $end] = $this->repo->weekBounds($monday);

        return $this->repo->findBetween($start, $end);
    }

    /**
     * Création d’évènement avec validation basique.
     * @return array{0:bool,1:array<string,string>,2:?DateTime} [ok, errors, startsAt]
     */
    public function create(
        string $title,
        string $dateYmd,
        string $timeHi,
        ?string $location,
        float $durationHours,
        ?int $createdBy
    ): array {
        $errors = [];

        $title = trim($title);
        $location = $location !== null ? trim($location) : null;

        if ($title === '') {
            $errors['titre'] = 'Requis.';
        }
        if ($dateYmd === '') {
            $errors['date'] = 'Requis.';
        }
        if ($timeHi === '') {
            $errors['heure'] = 'Requis.';
        }

        // parse date+time
        $startsAt = null;
        if ($dateYmd !== '' && $timeHi !== '') {
            $startsAt = DateTime::createFromFormat('Y-m-d H:i', $dateYmd . ' ' . $timeHi) ?: null;
            if ($startsAt === null) {
                $errors['_global'] = 'Date/heure invalides.';
            }
        }

        $durationHours = max(0.5, $durationHours);
        $durationMinutes = (int)round($durationHours * 60);

        if ($errors !== []) {
            return [false, $errors, $startsAt];
        }

        // Invariant : startsAt non null ici
        $this->repo->insert($title, $startsAt, $durationMinutes, $location, $createdBy);

        return [true, [], $startsAt];
    }

    public function mondayOf(DateTime $dt): DateTime
    {
        $m = clone $dt;
        $weekday = (int)$m->format('N'); // 1 = lundi
        if ($weekday !== 1) {
            $m->modify('-' . ($weekday - 1) . ' days');
        }

        return $m;
    }
}
