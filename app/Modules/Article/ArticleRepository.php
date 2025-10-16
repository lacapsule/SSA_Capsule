<?php

declare(strict_types=1);

namespace App\Modules\Article;

use App\Modules\Article\Dto\ArticleDTO;
use Capsule\Domain\Repository\BaseRepository;
use PDO;

class ArticleRepository extends BaseRepository
{
    protected string $table = 'articles';
    protected string $primaryKey = 'id';

    public function __construct(PDO $pdo)
    {
        parent::__construct($pdo);
    }

    /**
     * Flux paginé d’événements à venir (date >= aujourd’hui), triés par date croissante puis heure croissante.
     * @return iterable<ArticleDTO>
     */
    public function findUpcoming(int $limit, int $offset): iterable
    {
        $sql = "
            SELECT a.*, u.username AS author_name
            FROM {$this->table} a
            LEFT JOIN users u ON a.author_id = u.id
            WHERE a.date_article >= :today
            ORDER BY a.date_article ASC, a.hours ASC
            LIMIT :limit OFFSET :offset
        ";
        $stmt = $this->pdo->prepare($sql);
        $today = date('Y-m-d');
        $stmt->bindValue(':today', $today, PDO::PARAM_STR);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            yield $this->hydrate($row);
        }
    }

    /**
     * @deprecated Utiliser findUpcoming($limit, $offset) pour maîtriser la charge.
     * @return iterable<ArticleDTO>
     */
    public function upcoming(): iterable
    {
        $sql = "
            SELECT a.*, u.username AS author_name
            FROM {$this->table} a
            LEFT JOIN users u ON a.author_id = u.id
            WHERE a.date_article >= :today
            ORDER BY a.date_article ASC, a.hours ASC
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['today' => date('Y-m-d')]);

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            yield $this->hydrate($row);
        }
    }

    /**
     * Articles d’un auteur (tri décroissant par date).
     * @return iterable<ArticleDTO>
     */
    public function findByAuthor(int $authorId): iterable
    {
        $sql = "
            SELECT a.*, u.username AS author_name
            FROM {$this->table} a
            LEFT JOIN users u ON a.author_id = u.id
            WHERE a.author_id = :author_id
            ORDER BY a.date_article DESC, a.hours DESC
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':author_id', $authorId, PDO::PARAM_INT);
        $stmt->execute();

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            yield $this->hydrate($row);
        }
    }

    public function findById(int $id): ?ArticleDTO
    {
        $stmt = $this->pdo->prepare("
            SELECT a.*, u.username AS author_name
            FROM {$this->table} a
            LEFT JOIN users u ON a.author_id = u.id
            WHERE a.id = :id
        ");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? $this->hydrate($row) : null;
    }

    /**
     * Tous les articles avec auteur (tri décroissant).
     * @return iterable<ArticleDTO>
     */
    public function getAllWithAuthor(): iterable
    {
        $stmt = $this->pdo->query("
            SELECT a.*, u.username AS author_name
            FROM {$this->table} a
            LEFT JOIN users u ON a.author_id = u.id
            ORDER BY a.date_article DESC, a.hours DESC
        ");

        if ($stmt) {
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                yield $this->hydrate($row);
            }
        }
    }

    /** @param array<string, mixed> $data */
    public function create(array $data): int
    {
        return $this->insert($data);
    }

    public function deleteByAuthor(int $authorId): int
    {
        $stmt = $this->pdo->prepare("DELETE FROM {$this->table} WHERE author_id = :author_id");
        $stmt->bindValue(':author_id', $authorId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->rowCount();
    }

    /** @param array<string, mixed> $data */
    private function hydrate(array $data): ArticleDTO
    {
        return new ArticleDTO(
            id: (int)($data['id'] ?? 0),
            titre: (string)($data['titre'] ?? ''),
            resume: (string)($data['resume'] ?? ''),
            description: isset($data['description']) ? (string)$data['description'] : null,
            date_article: (string)($data['date_article'] ?? ''),
            hours: (string)($data['hours'] ?? ''),
            image: isset($data['image']) ? (string)$data['image'] : null,
            lieu: isset($data['lieu']) ? (string)$data['lieu'] : null,
            created_at: (string)($data['created_at'] ?? ''),
            author_id: (int)($data['author_id'] ?? 0),
        );
    }
}
