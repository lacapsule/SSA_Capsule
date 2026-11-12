<?php

declare(strict_types=1);

namespace Capsule\Domain\Repository;

use PDO;

final class PartnerLogoRepository extends BaseRepository
{
    protected string $table = 'partner_logos';
    protected string $primaryKey = 'id';

    public function __construct(PDO $pdo)
    {
        parent::__construct($pdo);
    }

    /**
     * @return array<int,array{id:int,section_id:int,name:string,url:string,logo_path:string,position:int}>
     */
    public function findBySection(int $sectionId): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE section_id = :section_id ORDER BY position ASC, id ASC";

        /** @var array<int,array<string,mixed>> $rows */
        $rows = $this->queryAll($sql, ['section_id' => $sectionId]);

        return array_map(
            static fn (array $row): array => [
                'id' => (int) $row['id'],
                'section_id' => (int) $row['section_id'],
                'name' => (string) $row['name'],
                'url' => (string) $row['url'],
                'logo_path' => (string) $row['logo_path'],
                'position' => (int) $row['position'],
            ],
            $rows
        );
    }

    public function deleteBySection(int $sectionId): void
    {
        $sql = "DELETE FROM {$this->table} WHERE section_id = :section_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['section_id' => $sectionId]);
    }

    public function findById(int $id): ?array
    {
        $row = $this->find($id);

        if ($row === null) {
            return null;
        }

        return [
            'id' => (int) $row['id'],
            'section_id' => (int) $row['section_id'],
            'name' => (string) $row['name'],
            'url' => (string) $row['url'],
            'logo_path' => (string) $row['logo_path'],
            'position' => (int) $row['position'],
        ];
    }
}


