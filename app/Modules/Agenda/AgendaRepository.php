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
        // ✨ On ajoute 'color', 'description' et 'links' au SELECT
        $stmt = $this->pdo->prepare(
            'SELECT e.id, e.title, e.location, e.description, e.links, e.info, e.category_id,
                    e.starts_at, e.duration_minutes, e.created_by, e.color,
                    c.name as category_name, c.label as category_label, c.color as category_color
             FROM agenda_events e
             LEFT JOIN agenda_categories c ON e.category_id = c.id
             WHERE e.starts_at >= :start AND e.starts_at < :end
             ORDER BY e.starts_at'
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
                description: $row['description'] !== null ? (string) $row['description'] : null,
                links: $row['links'] !== null ? (string) $row['links'] : null,
                info: $row['info'] !== null ? (string) $row['info'] : null,
                categoryId: $row['category_id'] !== null ? (int) $row['category_id'] : null,
                categoryName: $row['category_name'] !== null ? (string) $row['category_name'] : null,
                categoryLabel: $row['category_label'] !== null ? (string) $row['category_label'] : null,
                categoryColor: $row['category_color'] !== null ? (string) $row['category_color'] : null,
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
        ?string $description,
        ?string $links,
        ?string $info,
        ?int $categoryId,
        ?int $createdBy,
        string $color
    ): int {
        $stmt = $this->pdo->prepare(
            'INSERT INTO agenda_events (title, starts_at, duration_minutes, location, description, links, info, category_id, created_by, color)
             VALUES (:title, :starts_at, :duration, :location, :description, :links, :info, :category_id, :created_by, :color)'
        );

        $stmt->execute([
            ':title' => $title,
            ':starts_at' => $startsAt->format('Y-m-d H:i:00'),
            ':duration' => $durationMinutes,
            ':location' => $location,
            ':description' => $description,
            ':links' => $links,
            ':info' => $info,
            ':category_id' => $categoryId,
            ':created_by' => $createdBy,
            ':color' => $color,
        ]);

        return (int) $this->pdo->lastInsertId(); // Retourner l'ID
    }

    public function update(int $id, string $title, DateTime $startsAt, int $durationMinutes, ?string $location, ?string $description, ?string $links, ?string $info, ?int $categoryId, string $color): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE agenda_events
             SET title = :title, starts_at = :starts_at, duration_minutes = :duration, location = :location, description = :description, links = :links, info = :info, category_id = :category_id, color = :color
             WHERE id = :id'
        );

        $stmt->execute([
            ':id' => $id,
            ':title' => $title,
            ':starts_at' => $startsAt->format('Y-m-d H:i:s'),
            ':duration' => $durationMinutes,
            ':location' => $location,
            ':description' => $description,
            ':links' => $links,
            ':info' => $info,
            ':category_id' => $categoryId,
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

    /** Catégories */
    public function getCategories(): array
    {
        $stmt = $this->pdo->query('SELECT id, name, label, color FROM agenda_categories ORDER BY label');
        return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    }

    public function createCategory(string $name, string $label, string $color): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO agenda_categories (name, label, color) VALUES (:name, :label, :color)'
        );
        $stmt->execute([
            ':name' => $name,
            ':label' => $label,
            ':color' => $color,
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    public function updateCategory(int $id, string $name, string $label, string $color): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE agenda_categories SET name = :name, label = :label, color = :color WHERE id = :id'
        );
        $stmt->execute([
            ':id' => $id,
            ':name' => $name,
            ':label' => $label,
            ':color' => $color,
        ]);
        return $stmt->rowCount() > 0;
    }

    public function deleteCategory(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM agenda_categories WHERE id = :id');
        $stmt->execute([':id' => $id]);
        return $stmt->rowCount() > 0;
    }

    public function findCategoryById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT id, name, label, color FROM agenda_categories WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }
}
