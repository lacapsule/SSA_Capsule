<?php

declare(strict_types=1);

namespace App\Modules\Agenda;

use App\Modules\Agenda\Dto\AgendaEventDTO;
use DateTimeImmutable;
use DateTime;
use PDO;

final class AgendaRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    /** @return array<AgendaEventDTO> */
    public function findBetween(DateTime $start, DateTime $end): array
    {
        // ✨ On ajoute 'color' au SELECT
        $stmt = $this->pdo->prepare(
            'SELECT id, title, location, starts_at, duration_minutes, created_by, color
             FROM agenda_events
             WHERE starts_at >= :start AND starts_at < :end
             ORDER BY starts_at'
        );

        $stmt->execute([
            ':start' => $start->format('Y-m-d H:i:s'),
            ':end' => $end->format('Y-m-d H:i:s'),
        ]);

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(
            fn(array $row) => new AgendaEventDTO(
                id: (int) $row['id'],
                title: (string) $row['title'],
                startsAt: new DateTimeImmutable($row['starts_at']),
                durationMinutes: (int) $row['duration_minutes'],
                location: $row['location'] !== null ? (string) $row['location'] : null,
                createdBy: $row['created_by'] !== null ? (int) $row['created_by'] : null,
                color: $row['color'] ?? '#3788d8',
            ),
            $rows
        );
    }

    public function insert(
        string $title,
        DateTime $startsAt,
        int $durationMinutes,
        ?string $location,
        ?int $createdBy,
        string $color
    ): int {
        // ✨ Ajout de la colonne color dans l'INSERT
        $stmt = $this->pdo->prepare(
            'INSERT INTO agenda_events (title, starts_at, duration_minutes, location, created_by, color)
             VALUES (:title, :starts_at, :duration, :location, :created_by, :color)'
        );

        $stmt->execute([
            ':title' => $title,
            ':starts_at' => $startsAt->format('Y-m-d H:i:00'),
            ':duration' => $durationMinutes,
            ':location' => $location,
            ':created_by' => $createdBy,
            ':color' => $color,
        ]);

        return (int) $this->pdo->lastInsertId(); // Retourner l'ID
    }

    public function update(int $id, string $title, DateTime $startsAt, int $durationMinutes, ?string $location, string $color): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE agenda_events
             SET title = :title, starts_at = :starts_at, duration_minutes = :duration, location = :location, color = :color
             WHERE id = :id'
        );

        $stmt->execute([
            ':id' => $id,
            ':title' => $title,
            ':starts_at' => $startsAt->format('Y-m-d H:i:s'),
            ':duration' => $durationMinutes,
            ':location' => $location,
            ':color' => $color,
        ]);

        return $stmt->rowCount() > 0;
    }

    /**  Ajout méthode delete */
    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM agenda_events WHERE id = :id');
        $stmt->execute([':id' => $id]);

        return $stmt->rowCount() > 0;
    }
}
