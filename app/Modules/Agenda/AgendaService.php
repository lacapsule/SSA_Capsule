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
        ?string $description,
        ?string $links,
        ?string $info,
        ?int $categoryId,
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

        if ($links !== null && $links !== '' && !filter_var($links, FILTER_VALIDATE_URL)) {
            $errors['links'] = 'Le lien doit être une URL valide.';
        }

        if ($errors !== []) {
            return [false, $errors, $startsAt];
        }

        $this->repo->insert(
            title: $title,
            startsAt: $startsAt,
            durationMinutes: $durationMinutes,
            location: $location,
            description: $description,
            links: $links,
            info: $info,
            categoryId: $categoryId,
            createdBy: $createdBy,
            color: $color
        );

        return [true, [], $startsAt];
    }

    /**
     * @return array{0:bool, 1:array<string,string>}
     */
    public function update(
        int $id,
        string $title,
        string $startStr,
        string $endStr,
        ?string $location,
        ?string $description,
        ?string $links,
        ?string $info,
        ?int $categoryId,
        string $color = '#3788d8'
    ): array
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

        if ($links !== null && $links !== '' && !filter_var($links, FILTER_VALIDATE_URL)) {
            $errors['links'] = 'Le lien doit être une URL valide.';
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

        $this->repo->update(
            id: $id,
            title: $title,
            startsAt: $startsAt,
            durationMinutes: (int)$durationMinutes,
            location: $location,
            description: $description,
            links: $links,
            info: $info,
            categoryId: $categoryId,
            color: $color
        );

        return [true, []];
    }

    public function delete(int $id): bool
    {
        return $this->repo->delete($id);
    }

    /** Catégories */
    public function listCategories(): array
    {
        return $this->repo->getCategories();
    }

    public function createCategory(string $name, string $label, string $color): array
    {
        $errors = [];
        if ($name === '') $errors['name'] = 'Nom requis';
        if ($label === '') $errors['label'] = 'Libellé requis';
        if ($color === '' || !preg_match('/^#[0-9a-fA-F]{6}$/', $color)) {
            $errors['color'] = 'Couleur hexadécimale requise (ex: #ff0000)';
        }
        if ($errors !== []) return ['errors' => $errors];
        $id = $this->repo->createCategory($name, $label, $color);
        return ['id' => $id];
    }

    public function updateCategory(int $id, string $name, string $label, string $color): array
    {
        $errors = [];
        if ($id <= 0) $errors['_global'] = 'ID invalide';
        if ($name === '') $errors['name'] = 'Nom requis';
        if ($label === '') $errors['label'] = 'Libellé requis';
        if ($color === '' || !preg_match('/^#[0-9a-fA-F]{6}$/', $color)) {
            $errors['color'] = 'Couleur hexadécimale requise (ex: #ff0000)';
        }
        if ($errors !== []) return ['errors' => $errors];
        $this->repo->updateCategory($id, $name, $label, $color);
        return [];
    }

    public function deleteCategory(int $id): array
    {
        if ($id <= 0) return ['errors' => ['_global' => 'ID invalide']];
        $this->repo->deleteCategory($id);
        return [];
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