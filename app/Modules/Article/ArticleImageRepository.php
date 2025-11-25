<?php

declare(strict_types=1);

namespace App\Modules\Article;

use PDO;

/**
 * Persistance des images liées aux articles.
 */
final class ArticleImageRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /**
     * @return list<string> chemins publics des images
     */
    public function findPathsByArticle(int $articleId): array
    {
        $stmt = $this->pdo->prepare('
            SELECT path
            FROM article_images
            WHERE article_id = :article_id
            ORDER BY position ASC, id ASC
        ');
        $stmt->bindValue(':article_id', $articleId, PDO::PARAM_INT);
        $stmt->execute();

        /** @var list<string> */
        return array_map(
            static fn (array $row): string => (string) $row['path'],
            $stmt->fetchAll(PDO::FETCH_ASSOC) ?: []
        );
    }

    /**
     * Remplace entièrement les images associées à un article.
     *
     * @param list<string> $paths
     */
    public function replaceImages(int $articleId, array $paths): void
    {
        $this->pdo->beginTransaction();
        try {
            $this->deleteByArticle($articleId);
            $position = 0;
            foreach ($paths as $path) {
                $this->insert($articleId, $path, $position++);
            }
            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function deleteByArticle(int $articleId): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM article_images WHERE article_id = :article_id');
        $stmt->bindValue(':article_id', $articleId, PDO::PARAM_INT);
        $stmt->execute();
    }

    private function insert(int $articleId, string $path, int $position): void
    {
        $stmt = $this->pdo->prepare('
            INSERT INTO article_images (article_id, path, position)
            VALUES (:article_id, :path, :position)
        ');
        $stmt->bindValue(':article_id', $articleId, PDO::PARAM_INT);
        $stmt->bindValue(':path', $path, PDO::PARAM_STR);
        $stmt->bindValue(':position', $position, PDO::PARAM_INT);
        $stmt->execute();
    }
}

