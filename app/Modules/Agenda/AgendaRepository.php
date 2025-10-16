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
        $stmt = $this->pdo->prepare(
            'SELECT id, title, location, starts_at, duration_minutes, created_by
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
            fn (array $row) => new AgendaEventDTO(
                id: (int) $row['id'],
                title: (string) $row['title'],
                startsAt: new DateTimeImmutable($row['starts_at']),
                durationMinutes: (int) $row['duration_minutes'],
                location: $row['location'] !== null ? (string) $row['location'] : null,
                createdBy: $row['created_by'] !== null ? (int) $row['created_by'] : null,
            ),
            $rows
        );
    }

    public function insert(
        string $title,
        DateTime $startsAt,
        int $durationMinutes,
        ?string $location,
        ?int $createdBy
    ): int {
        $stmt = $this->pdo->prepare(
            'INSERT INTO agenda_events (title, starts_at, duration_minutes, location, created_by)
             VALUES (:title, :starts_at, :duration, :location, :created_by)'
        );

        $stmt->execute([
            ':title' => $title,
            ':starts_at' => $startsAt->format('Y-m-d H:i:00'),
            ':duration' => $durationMinutes,
            ':location' => $location,
            ':created_by' => $createdBy,
        ]);

        return (int) $this->pdo->lastInsertId(); // ✨ Retourner l'ID
    }

    /** ✨ Ajout méthode delete */
    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM agenda_events WHERE id = :id');
        $stmt->execute([':id' => $id]);

        return $stmt->rowCount() > 0;
    }
}
