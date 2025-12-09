<?php

declare(strict_types=1);

namespace App\Repository;

use App\Dto\ArticleDTO;
use CapsuleLib\Repository\BaseRepository;
use PDO;

/**
 * Repository dédié à la gestion des événements.
 *
 * Fournit les opérations CRUD spécifiques aux événements,
 * avec un mapping vers des DTOs typés (`ArticleDTO`).
 *
 * Hérite du `BaseRepository` pour les opérations SQL basiques.
 */
class ArticleRepository extends BaseRepository
{
    /**
     * Nom de la table associée aux événements.
     *
     * @var string
     */
    protected string $table = 'articles';

    /**
     * Clé primaire de la table.
     *
     * @var string
     */
    protected string $primaryKey = 'id';

    /**
     * Constructeur.
     *
     * @param PDO $pdo Instance PDO pour la connexion à la base.
     */
    public function __construct(PDO $pdo)
    {
        parent::__construct($pdo);
    }

    /**
     * Récupère tous les événements à venir (date >= aujourd’hui).
     *
     * Les événements sont triés par date croissante.
     *
     * @return ArticleDTO[] Liste des événements futurs.
     */
    public function upcoming(): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM {$this->table}
             WHERE date_article >= :today
             ORDER BY date_article ASC"
        );
        $stmt->execute(['today' => date('Y-m-d')]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return array_map([$this, 'hydrate'], $rows ?: []);
    }

    /**
     * Récupère les événements créés par un auteur donné.
     *
     * Triés par date décroissante (les plus récents en premier).
     *
     * @param int $authorId ID de l’auteur.
     * @return ArticleDTO[] Liste des événements de l’auteur.
     */
    public function findByAuthor(int $authorId): array
    {
        $rows = $this->query(
            "SELECT * FROM {$this->table} WHERE author_id = :author_id ORDER BY date_article DESC",
            ['author_id' => $authorId]
        );
        return array_map([$this, 'hydrate'], $rows ?: []);
    }

    /**
     * Récupère un événement via son ID.
     *
     * @param int $id ID de l’événement.
     * @return ArticleDTO|null DTO de l’événement ou null si non trouvé.
     */
    public function findById(int $id): ?ArticleDTO
    {
        $row = $this->find($id);
        return is_array($row) ? $this->hydrate($row) : null;
    }

    /**
     * Récupère tous les événements (DTO).
     *
     * @return ArticleDTO[]
     */
    public function all(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM {$this->table} ORDER BY date_article DESC");
        $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        return array_map([$this, 'hydrate'], $rows ?: []);
    }

    /**
     * Crée un nouvel événement en base.
     *
     * N’accepte pas de champ image.
     *
     * @param array<string, mixed> $data Données de l’événement.
     * @return int ID nouvellement créé.
     */
    public function create(array $data): int
    {
        return $this->insert($data);
    }

    /**
     * Supprime tous les événements d’un auteur donné.
     *
     * Utile lors de la suppression d’un utilisateur ou purge.
     *
     * @param int $authorId ID de l’auteur.
     * @return int Nombre de lignes supprimées.
     */
    public function deleteByAuthor(int $authorId): int
    {
        $stmt = $this->pdo->prepare("DELETE FROM {$this->table} WHERE author_id = :author_id");
        $stmt->execute(['author_id' => $authorId]);
        return $stmt->rowCount();
    }

    /**
     * Convertit un tableau de données SQL en DTO ArticleDTO.
     *
     * @param array<string, mixed> $data Données issues de la requête SQL.
     * @return ArticleDTO Objet DTO strictement typé.
     */
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
            author: isset($data['author']) ? (string)$data['author'] : null
        );
    }
}
