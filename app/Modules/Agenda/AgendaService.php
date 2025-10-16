<?php

declare(strict_types=1);

namespace App\Modules\Agenda;

use App\Dto\AgendaEventDTO;
use DateInterval;
use DateTime;

final class AgendaService
{
    public function __construct(private AgendaRepository $repo)
    {
    }

    /** @return array<AgendaEventDTO> */
    public function getWeekEvents(DateTime $monday): array
    {
        [$start, $end] = $this->weekBounds($monday);

        return $this->repo->findBetween($start, $end);
    }

    /**
     * @return array{0:bool, 1:array<string,string>, 2:?DateTime}
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

        if ($title === '') {
            $errors['title'] = 'Le titre est requis.'; // ✨ Clé normalisée
        }
        if ($dateYmd === '') {
            $errors['date'] = 'La date est requise.';
        }
        if ($timeHi === '') {
            $errors['time'] = 'L\'heure est requise.';
        }

        $startsAt = null;
        if ($dateYmd !== '' && $timeHi !== '') {
            $startsAt = DateTime::createFromFormat('Y-m-d H:i', "$dateYmd $timeHi") ?: null;
            if ($startsAt === null) {
                $errors['_global'] = 'Date ou heure invalide.';
            }
        }

        $durationMinutes = (int) round(max(0.25, $durationHours) * 60); // ✨ Min 15min

        if ($errors !== []) {
            return [false, $errors, $startsAt];
        }

        $this->repo->insert($title, $startsAt, $durationMinutes, $location, $createdBy);

        return [true, [], $startsAt];
    }

    /** ✨ Ajout méthode delete */
    public function delete(int $id): bool
    {
        return $this->repo->delete($id);
    }

    public function mondayOf(DateTime $dt): DateTime
    {
        $monday = clone $dt;
        $weekday = (int) $monday->format('N');

        if ($weekday !== 1) {
            $monday->modify('-' . ($weekday - 1) . ' days');
        }

        return $monday->setTime(0, 0);
    }

    /** @return array{DateTime, DateTime} */
    private function weekBounds(DateTime $monday): array
    {
        $start = (clone $monday)->setTime(0, 0, 0);
        $end = (clone $start)->add(new DateInterval('P7D'));

        return [$start, $end];
    }
}
