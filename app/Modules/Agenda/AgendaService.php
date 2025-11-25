<?php

declare(strict_types=1);

namespace App\Modules\Agenda;

// Correction de l'import (le DTO est dans Modules/Agenda/Dto, pas App/Dto)
use App\Modules\Agenda\Dto\AgendaEventDTO; 
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
     * Récupère les événements pour une période donnée.
     * @return array<AgendaEventDTO>
     */
    public function getEvents(string $startStr, string $endStr): array
    {
        try {
            $start = new DateTime($startStr);
            $end = new DateTime($endStr);
            // La méthode findBetween est exclusive sur la date de fin, on ajoute un jour.
            $end->modify('+1 day');
        } catch (\Exception $e) {
            return [];
        }

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
        ?int $createdBy,
        string $color = '#3788d8',
    ): array {
        $errors = [];
        $title = trim($title);

        if ($title === '') {
            $errors['title'] = 'Le titre est requis.';
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

        $durationMinutes = (int) round(max(0.0, $durationHours) * 60);
        // Permettre des événements jusqu'à 30 jours (43200 minutes)
        $maxDurationMinutes = 30 * 24 * 60; // 43200 minutes = 30 jours

        if ($durationMinutes < 30 || $durationMinutes > $maxDurationMinutes) {
            $errors['duration'] = 'La durée doit être comprise entre 30 minutes et 30 jours.';
        }

        if ($errors !== []) {
            return [false, $errors, $startsAt];
        }

        // ✨ CORRECTION ICI : Ajout de $color qui manquait dans l'appel insert
        $this->repo->insert($title, $startsAt, $durationMinutes, $location, $createdBy, $color);

        return [true, [], $startsAt];
    }

    /**
     * @return array{0:bool, 1:array<string,string>}
     */
    public function update(int $id, string $title, string $startStr, string $endStr, ?string $description, string $color = '#3788d8'): array
    {
        $errors = [];
        $title = trim($title);

        if ($title === '') {
            $errors['title'] = 'Le titre est requis.';
        }
        if ($startStr === '') {
            $errors['start'] = 'La date de début est requise.';
        }
        if ($endStr === '') {
            $errors['end'] = 'La date de fin est requise.';
        }

        $startsAt = null;
        if ($startStr) {
            try {
                $startsAt = new DateTime($startStr);
            } catch (\Exception $e) {
                $errors['start'] = 'Format de date de début invalide.';
            }
        }

        $endsAt = null;
        if ($endStr) {
            try {
                $endsAt = new DateTime($endStr);
            } catch (\Exception $e) {
                $errors['end'] = 'Format de date de fin invalide.';
            }
        }

        if ($startsAt && $endsAt && $startsAt >= $endsAt) {
            $errors['_global'] = 'La date de fin doit être après la date de début.';
        }

        if (!empty($errors)) {
            return [false, $errors];
        }

        $durationMinutes = ($endsAt->getTimestamp() - $startsAt->getTimestamp()) / 60;
        // Permettre des événements jusqu'à 30 jours (43200 minutes)
        $maxDurationMinutes = 30 * 24 * 60; // 43200 minutes = 30 jours

        if ($durationMinutes < 30 || $durationMinutes > $maxDurationMinutes) {
            $errors['duration'] = 'La durée doit être comprise entre 30 minutes et 30 jours.';
        }

        if ($errors !== []) {
            return [false, $errors];
        }

        $this->repo->update($id, $title, $startsAt, (int)$durationMinutes, $description, $color);

        return [true, []];
    }

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