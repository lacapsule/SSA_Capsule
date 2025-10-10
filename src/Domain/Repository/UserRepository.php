<?php

declare(strict_types=1);

namespace Capsule\Domain\Repository;

use Capsule\Domain\DTO\UserDTO;
use PDO;

/**
 * Repository pour la gestion des utilisateurs.
 *
 * Étend BaseRepository pour ajouter des méthodes spécifiques au domaine utilisateur :
 * - Recherche par ID, nom d’utilisateur, email
 * - Vérification d’existence pour unicité
 * - Hydratation d’objets UserDTO
 */
class UserRepository extends BaseRepository
{
    /**
     * @var string Nom de la table utilisateur.
     */
    protected string $table = 'users';

    /**
     * @var string Clé primaire de la table.
     */
    protected string $primaryKey = 'id';

    public function __construct(PDO $pdo)
    {
        parent::__construct($pdo);
    }

    /**
     * Recherche un utilisateur par son identifiant.
     *
     * @param int $id Identifiant utilisateur.
     * @return UserDTO|null Objet UserDTO ou null si non trouvé.
     */
    public function findById(int $id): ?UserDTO
    {
        $row = $this->find($id);

        return $row ? $this->hydrate($row) : null;
    }

    /**
     * Recherche un utilisateur par son nom d’utilisateur.
     *
     * @param string $username Nom d’utilisateur.
     * @return UserDTO|null Objet UserDTO ou null si non trouvé.
     */
    public function findByUsername(string $username): ?UserDTO
    {
        $row = $this->queryOne("SELECT * FROM {$this->table} WHERE username = :username", [
            'username' => $username,
        ]);

        return $row ? $this->hydrate($row) : null;
    }

    /**
     * Recherche un utilisateur par son email.
     *
     * @param string $email Adresse email.
     * @return UserDTO|null Objet UserDTO ou null si non trouvé.
     */
    public function findByEmail(string $email): ?UserDTO
    {
        $row = $this->queryOne("SELECT * FROM {$this->table} WHERE email = :email", [
            'email' => $email,
        ]);

        return $row ? $this->hydrate($row) : null;
    }

    /**
     * Récupère tous les utilisateurs.
     *
     * @return UserDTO[] Liste d’objets UserDTO.
     */
    public function allUsers(): array
    {
        $rows = $this->all();

        return array_map([$this, 'hydrate'], $rows);
    }

    /**
     * Vérifie l’existence d’un nom d’utilisateur.
     *
     * @param string $username Nom d’utilisateur.
     * @return bool True si le nom existe, false sinon.
     */
    public function existsUsername(string $username): bool
    {
        $stmt = $this->pdo->prepare("SELECT 1 FROM {$this->table} WHERE username = :username");
        $stmt->execute(['username' => $username]);

        return (bool)$stmt->fetchColumn();
    }

    /**
     * Vérifie l’existence d’une adresse email.
     *
     * @param string $email Adresse email.
     * @return bool True si l’email existe, false sinon.
     */
    public function existsEmail(string $email): bool
    {
        $stmt = $this->pdo->prepare("SELECT 1 FROM {$this->table} WHERE email = :email");
        $stmt->execute(['email' => $email]);

        return (bool)$stmt->fetchColumn();
    }


    public function getPasswordHashById(int $id): ?string
    {
        $row = $this->queryOne("SELECT password_hash FROM {$this->table} WHERE {$this->primaryKey} = :id", [
            'id' => $id,
        ]);

        return $row['password_hash'] ?? null;
    }

    public function updatePasswordHash(int $id, string $newHash): bool
    {
        // délègue au update() générique du BaseRepository
        return $this->update($id, ['password_hash' => $newHash]);
    }

    /**
     * Hydrate un objet UserDTO à partir d’un tableau associatif de données SQL.
     *
     * @param array<string,mixed> $data Ligne SQL.
     * @return UserDTO Objet UserDTO construit.
     */
    private function hydrate(array $data): UserDTO
    {
        return new UserDTO(
            id: (int)$data['id'],
            username: $data['username'],
            password_hash: $data['password_hash'],
            role: $data['role'],
            email: $data['email'],
            created_at: $data['created_at'],
        );
    }
}
