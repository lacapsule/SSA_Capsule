<?php

declare(strict_types=1);

namespace App\Repository;

use DateInterval;
use DateTime;
use PDO;

final class AgendaRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    /**
     * @return list<array{date:string,time:string,title:string,location:string,duration:float}>
     */
    public function findBetween(DateTime $start, DateTime $end): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT title, location,
                    DATE_FORMAT(starts_at, "%d-%m-%Y") AS date,
                    DATE_FORMAT(starts_at, "%H:%i")   AS time,
                    (duration_minutes / 60.0)         AS duration
             FROM agenda_events
             WHERE starts_at >= :start AND starts_at < :end
             ORDER BY starts_at'
        );
        $stmt->execute([
            ':start' => $start->format('Y-m-d H:i:s'),
            ':end' => $end->format('Y-m-d H:i:s'),
        ]);

        /** @var list<array{date:string,time:string,title:string,location:string,duration:float}> $rows */
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        return $rows;
    }

    public function insert(
        string $title,
        DateTime $startsAt,
        int $durationMinutes,
        ?string $location,
        ?int $createdBy
    ): void {
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
    }

    public function weekBounds(DateTime $monday): array
    {
        $start = (clone $monday)->setTime(0, 0, 0);
        $end = (clone $monday)->add(new DateInterval('P7D'))->setTime(0, 0, 0);

        return [$start, $end];
    }
}
