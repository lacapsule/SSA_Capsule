<?php

declare(strict_types=1);

namespace Capsule\Domain\Repository;

use PDO;

final class PartnerSectionRepository extends BaseRepository
{
    protected string $table = 'partner_sections';
    protected string $primaryKey = 'id';

    public function __construct(PDO $pdo)
    {
        parent::__construct($pdo);
    }

    /**
     * @return array<int,array{id:int,name:string,slug:string,description:?string,kind:string,position:int,is_active:int}>
     */
    public function findAllOrdered(): array
    {
        $sql = "SELECT * FROM {$this->table} ORDER BY position ASC, id ASC";

        /** @var array<int,array<string,mixed>> $rows */
        $rows = $this->queryAll($sql);

        return array_map(
            static fn (array $row): array => [
                'id' => (int) $row['id'],
                'name' => (string) $row['name'],
                'slug' => (string) $row['slug'],
                'description' => $row['description'] !== null ? (string) $row['description'] : null,
                'kind' => (string) $row['kind'],
                'position' => (int) $row['position'],
                'is_active' => (int) ($row['is_active'] ?? 1),
            ],
            $rows
        );
    }

    /**
     * @return array<int,array{id:int,name:string,slug:string,description:?string,kind:string,position:int,is_active:int}>
     */
    public function findByKind(string $kind): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE kind = :kind AND is_active = 1 ORDER BY position ASC, id ASC";

        /** @var array<int,array<string,mixed>> $rows */
        $rows = $this->queryAll($sql, ['kind' => $kind]);

        return array_map(
            static fn (array $row): array => [
                'id' => (int) $row['id'],
                'name' => (string) $row['name'],
                'slug' => (string) $row['slug'],
                'description' => $row['description'] !== null ? (string) $row['description'] : null,
                'kind' => (string) $row['kind'],
                'position' => (int) $row['position'],
                'is_active' => (int) ($row['is_active'] ?? 1),
            ],
            $rows
        );
    }

    public function findById(int $id): ?array
    {
        $row = $this->find($id);

        if ($row === null) {
            return null;
        }

        return [
            'id' => (int) $row['id'],
            'name' => (string) $row['name'],
            'slug' => (string) $row['slug'],
            'description' => $row['description'] !== null ? (string) $row['description'] : null,
            'kind' => (string) $row['kind'],
            'position' => (int) $row['position'],
            'is_active' => (int) ($row['is_active'] ?? 1),
        ];
    }

    public function deleteSection(int $id): void
    {
        $this->delete($id);
    }

    public function exists(int $id): bool
    {
        $row = $this->find($id);
        return $row !== null;
    }
}


