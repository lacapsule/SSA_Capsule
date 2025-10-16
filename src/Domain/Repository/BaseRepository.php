<?php

declare(strict_types=1);

namespace Capsule\Domain\Repository;

use PDO;
use Stringable;

/**
 * BaseRepository générique (CRUD minimal)
 *
 * @psalm-type SqlRow = array<string,mixed>
 */
abstract class BaseRepository
{
    protected PDO $pdo;
    protected string $table;
    protected string $primaryKey;

    public function __construct(PDO $pdo)
    {
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $this->pdo = $pdo;
    }

    /**
     * Trouve un enregistrement par son identifiant
     *
     * @param int|string $id
     * @return array<string,mixed>|null
     */
    public function find(int|string $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE {$this->primaryKey} = :id");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    /**
     * Récupère tous les enregistrements
     *
     * @return array<int,array<string,mixed>>
     */
    public function all(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM {$this->table}");

        return $stmt->fetchAll();
    }

    /**
     * Exécute une requête retournant une seule ligne
     *
     * @param string $sql
     * @param array<string,int|float|string|bool|null|Stringable> $params
     * @return array<string,mixed>|null
     */
    protected function queryOne(string $sql, array $params = []): ?array
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(self::stringifyParams($params));
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    /**
     * Exécute une requête retournant plusieurs lignes
     *
     * @param string $sql
     * @param array<string,int|float|string|bool|null|Stringable> $params
     * @return array<int,array<string,mixed>>
     */
    protected function queryAll(string $sql, array $params = []): array
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(self::stringifyParams($params));

        return $stmt->fetchAll();
    }

    /**
     * Insère un enregistrement et retourne le nouvel ID
     *
     * @param array<string,mixed> $data colonne => valeur
     * @return int nouvel ID
     */
    public function insert(array $data): int
    {
        $cols = array_keys($data);
        $fields = implode(', ', $cols);
        $placeholders = implode(', ', array_map(static fn ($c) => ':' . $c, $cols));

        $sql = "INSERT INTO {$this->table} ($fields) VALUES ($placeholders)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(self::stringifyParams($data));

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Alias de insert() pour cohérence sémantique
     *
     * @param array<string,mixed> $data
     * @return int nouvel ID
     */
    public function create(array $data): int
    {
        return $this->insert($data);
    }

    /**
     * Met à jour un enregistrement par ID
     *
     * @param int|string $id
     * @param array<string,mixed> $data
     * @return bool
     */
    public function update(int|string $id, array $data): bool
    {
        $set = implode(', ', array_map(static fn ($c) => "$c = :$c", array_keys($data)));
        $sql = "UPDATE {$this->table} SET $set WHERE {$this->primaryKey} = :__pk";
        $stmt = $this->pdo->prepare($sql);

        $params = self::stringifyParams($data + ['__pk' => $id]);

        return $stmt->execute($params);
    }

    /**
     * Supprime un enregistrement par ID
     *
     * @param int|string $id
     * @return bool
     */
    public function delete(int|string $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM {$this->table} WHERE {$this->primaryKey} = :id");

        return $stmt->execute(['id' => $id]);
    }

    /**
     * Normalise les valeurs pour PDO
     *
     * @param array<string,int|float|string|bool|null|Stringable> $params
     * @return array<string,int|float|string|null>
     */
    private static function stringifyParams(array $params): array
    {
        $out = [];
        foreach ($params as $k => $v) {
            if ($v instanceof Stringable) {
                $out[$k] = (string) $v;
            } elseif (is_bool($v)) {
                $out[$k] = $v ? 1 : 0;
            } else {
                $out[$k] = $v;
            }
        }

        return $out;
    }
}
