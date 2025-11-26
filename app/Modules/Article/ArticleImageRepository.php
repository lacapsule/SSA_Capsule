<?php

declare(strict_types=1);

namespace App\Modules\Article;

use Capsule\Domain\Repository\BaseRepository;
use PDO;
use PDOException;

/**
 * Gestion des images (et vidéos) associées aux articles.
 *
 * Table : article_images
 *  - article_id (FK articles.id)
 *  - path       (chemin public relatif)
 *  - position   (ordre d'affichage)
 */
final class ArticleImageRepository extends BaseRepository
{
    protected string $table = 'article_images';
    protected string $primaryKey = 'id';

    public function __construct(PDO $pdo)
    {
        parent::__construct($pdo);
    }

    /**
     * Retourne les chemins d’images triés par position croissante.
     *
     * @return list<string>
     */
    public function findPathsByArticle(int $articleId): array
    {
        return array_map(
            static fn (array $media): string => (string) $media['path'],
            $this->findMediaByArticle($articleId)
        );
    }

    /**
     * @return list<array{id:int,article_id:int,path:string,position:int}>
     */
    public function findMediaByArticle(int $articleId): array
    {
        if ($articleId <= 0) {
            return [];
        }

        $stmt = $this->pdo->prepare("
            SELECT id, article_id, path, position
            FROM {$this->table}
            WHERE article_id = :article_id
            ORDER BY position ASC, id ASC
        ");
        $stmt->bindValue(':article_id', $articleId, PDO::PARAM_INT);
        $stmt->execute();

        /** @var list<array{id:int,article_id:int,path:string,position:int}> $rows */
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(static function (array $row): array {
            return [
                'id' => (int) $row['id'],
                'article_id' => (int) $row['article_id'],
                'path' => trim((string) $row['path']),
                'position' => (int) $row['position'],
            ];
        }, $rows);
    }

    public function findMediaById(int $mediaId): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT id, article_id, path, position
            FROM {$this->table}
            WHERE id = :id
        ");
        $stmt->bindValue(':id', $mediaId, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        return [
            'id' => (int) $row['id'],
            'article_id' => (int) $row['article_id'],
            'path' => trim((string) $row['path']),
            'position' => (int) $row['position'],
        ];
    }

    /**
     * Remplace l’ensemble des médias d’un article par les chemins fournis.
     *
     * @param list<string> $paths
     */
    public function replaceImages(int $articleId, array $paths): void
    {
        if ($articleId <= 0) {
            return;
        }

        $this->pdo->beginTransaction();

        try {
            $this->deleteByArticle($articleId);

            if ($paths !== []) {
                $insert = $this->pdo->prepare("
                    INSERT INTO {$this->table} (article_id, path, position)
                    VALUES (:article_id, :path, :position)
                ");

                foreach ($paths as $index => $path) {
                    $insert->execute([
                        ':article_id' => $articleId,
                        ':path' => $path,
                        ':position' => $index,
                    ]);
                }
            }

            $this->pdo->commit();
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function deleteByArticle(int $articleId): void
    {
        if ($articleId <= 0) {
            return;
        }

        $stmt = $this->pdo->prepare("
            DELETE FROM {$this->table}
            WHERE article_id = :article_id
        ");
        $stmt->bindValue(':article_id', $articleId, PDO::PARAM_INT);
        $stmt->execute();
    }

    public function deleteById(int $mediaId): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM {$this->table} WHERE id = :id");
        $stmt->bindValue(':id', $mediaId, PDO::PARAM_INT);

        return $stmt->execute();
    }

    public function updatePath(int $mediaId, string $newPath): void
    {
        $stmt = $this->pdo->prepare("
            UPDATE {$this->table}
            SET path = :path
            WHERE id = :id
        ");
        $stmt->execute([
            ':path' => $newPath,
            ':id' => $mediaId,
        ]);
    }

    public function resequencePositions(int $articleId): void
    {
        $media = $this->findMediaByArticle($articleId);
        $stmt = $this->pdo->prepare("
            UPDATE {$this->table}
            SET position = :position
            WHERE id = :id
        ");

        foreach ($media as $index => $item) {
            $stmt->execute([
                ':position' => $index,
                ':id' => $item['id'],
            ]);
        }
    }
}

