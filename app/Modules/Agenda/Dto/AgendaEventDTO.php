<?php

declare(strict_types=1);

namespace App\Modules\Agenda\Dto;

use DateTimeImmutable;

final class AgendaEventDTO
{
    public function __construct(
        public readonly int $id,
        public readonly string $title,
        public readonly DateTimeImmutable $startsAt,
        public readonly int $durationMinutes,
        public readonly ?string $location = null,
        public readonly ?int $createdBy = null,
        public string $color = '#3788d8' // Ajouter si nécessaire
    ) {}

    /** Méthode helper calculée à la demande */
    public function endsAt(): DateTimeImmutable
    {
        return $this->startsAt->modify("+{$this->durationMinutes} minutes");
    }

    /** Helper pour affichage */
    public function durationHours(): float
    {
        return round($this->durationMinutes / 60, 1);
    }
}
