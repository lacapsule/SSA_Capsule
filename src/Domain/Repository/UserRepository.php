<?php

declare(strict_types=1);

namespace Capsule\Domain\Repository;

use Capsule\Domain\DTO\UserDTO;
use PDO;

/**
 * UserRepository - Gestion des utilisateurs en base de données
 *
 * Responsabilités :
 * - CRUD utilisateurs
 * - Recherche par username, email, ID
 * - Vérification d'existence (unicité)
 * - Gestion des mots de passe
 *
 * Architecture :
 * - Étend BaseRepository pour les opérations génériques
 * - Hydrate les résultats SQL en objets UserDTO
 * - Toutes les méthodes de recherche retournent des DTOs (immutables)
 *
 * Sécurité :
 * - Requêtes préparées (protection SQL injection)
 * - Password hash uniquement (jamais de mots de passe en clair)
 * - Validation des inputs côté appelant (Service layer)
 */
final class UserRepository extends BaseRepository
{
    protected string $table = 'users';
    protected string $primaryKey = 'id';

    public function __construct(PDO $pdo)
    {
        parent::__construct($pdo);
    }

    /**
     * Recherche un utilisateur par son ID
     *
     * @param int $id Identifiant utilisateur
     * @return UserDTO|null DTO ou null si non trouvé
     */
    public function findById(int $id): ?UserDTO
    {
        $row = $this->find($id);

        return $row ? $this->hydrate($row) : null;
    }

    /**
     * Recherche un utilisateur par son username
     *
     * Utilisé principalement pour l'authentification
     *
     * @param string $username Nom d'utilisateur (sensible à la casse)
     * @return UserDTO|null DTO ou null si non trouvé
     */
    public function findByUsername(string $username): ?UserDTO
    {
        $row = $this->queryOne(
            "SELECT * FROM {$this->table} WHERE username = :username LIMIT 1",
            ['username' => $username]
        );

        return $row ? $this->hydrate($row) : null;
    }

    /**
     * Recherche un utilisateur par son email
     *
     * @param string $email Adresse email (sensible à la casse)
     * @return UserDTO|null DTO ou null si non trouvé
     */
    public function findByEmail(string $email): ?UserDTO
    {
        $row = $this->queryOne(
            "SELECT * FROM {$this->table} WHERE email = :email LIMIT 1",
            ['email' => $email]
        );

        return $row ? $this->hydrate($row) : null;
    }

    /**
     * Récupère tous les utilisateurs
     *
     * ⚠️ Attention : peut être coûteux sur de grosses tables
     * Privilégier une pagination via queryAll() si nécessaire
     *
     * @return list<UserDTO> Liste de DTOs (peut être vide)
     */
    public function findAll(): array
    {
        $rows = $this->all();

        return array_map([$this, 'hydrate'], $rows);
    }

    /**
     * Récupère les utilisateurs avec pagination
     *
     * @param int $limit Nombre de résultats par page
     * @param int $offset Décalage (page * limit)
     * @return list<UserDTO> Liste de DTOs (peut être vide)
     */
    public function findPaginated(int $limit = 20, int $offset = 0): array
    {
        $rows = $this->queryAll(
            "SELECT * FROM {$this->table} ORDER BY created_at DESC LIMIT :limit OFFSET :offset",
            ['limit' => $limit, 'offset' => $offset]
        );

        return array_map([$this, 'hydrate'], $rows);
    }

    /**
     * Compte le nombre total d'utilisateurs
     *
     * Utile pour la pagination
     *
     * @return int Nombre total d'utilisateurs
     */
    public function count(): int
    {
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM {$this->table}");

        return (int) $stmt->fetchColumn();
    }

    /**
     * Vérifie si un username existe déjà
     *
     * Utilisé pour validation d'unicité lors de la création
     *
     * @param string $username Nom d'utilisateur à vérifier
     * @param int|null $excludeId ID à exclure (pour édition)
     * @return bool True si existe, false sinon
     */
    public function existsUsername(string $username, ?int $excludeId = null): bool
    {
        $sql = "SELECT 1 FROM {$this->table} WHERE username = :username";
        $params = ['username' => $username];

        if ($excludeId !== null) {
            $sql .= " AND {$this->primaryKey} != :excludeId";
            $params['excludeId'] = $excludeId;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return (bool) $stmt->fetchColumn();
    }

    /**
     * Vérifie si un email existe déjà
     *
     * Utilisé pour validation d'unicité lors de la création
     *
     * @param string $email Email à vérifier
     * @param int|null $excludeId ID à exclure (pour édition)
     * @return bool True si existe, false sinon
     */
    public function existsEmail(string $email, ?int $excludeId = null): bool
    {
        $sql = "SELECT 1 FROM {$this->table} WHERE email = :email";
        $params = ['email' => $email];

        if ($excludeId !== null) {
            $sql .= " AND {$this->primaryKey} != :excludeId";
            $params['excludeId'] = $excludeId;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return (bool) $stmt->fetchColumn();
    }

    /**
     * Récupère uniquement le hash du mot de passe
     *
     * Optimisation : évite de charger toutes les données utilisateur
     * Utilisé pour la vérification de mot de passe actuel
     *
     * @param int $id ID utilisateur
     * @return string|null Hash ou null si utilisateur n'existe pas
     */
    public function getPasswordHashById(int $id): ?string
    {
        $row = $this->queryOne(
            "SELECT password_hash FROM {$this->table} WHERE {$this->primaryKey} = :id LIMIT 1",
            ['id' => $id]
        );

        return $row['password_hash'] ?? null;
    }

    /**
     * Met à jour le hash du mot de passe
     *
     * Utilisé après changement de mot de passe
     *
     * @param int $id ID utilisateur
     * @param string $newHash Nouveau hash (déjà hashé via password_hash())
     * @return bool True si mise à jour réussie, false sinon
     */
    public function updatePasswordHash(int $id, string $newHash): bool
    {
        return $this->update($id, ['password_hash' => $newHash]);
    }

    /**
     * Crée un nouvel utilisateur
     *
     * @param array{username: string, password_hash: string, email: string, role: string} $data
     * @return int ID du nouvel utilisateur
     * @throws \PDOException Si contrainte d'unicité violée
     */
    public function createUser(array $data): int
    {
        return $this->create([
            'username' => $data['username'],
            'password_hash' => $data['password_hash'],
            'email' => $data['email'],
            'role' => $data['role'],
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Met à jour un utilisateur existant
     *
     * @param int $id ID utilisateur
     * @param array<string,mixed> $data Données à mettre à jour (username, email, role)
     * @return bool True si mise à jour réussie, false sinon
     */
    public function updateUser(int $id, array $data): bool
    {
        // Filtrer uniquement les champs autorisés
        $allowed = array_intersect_key($data, array_flip([
            'username',
            'email',
            'role',
        ]));

        if (empty($allowed)) {
            return false;
        }

        return $this->update($id, $allowed);
    }

    /**
     * Supprime un utilisateur
     *
     * @param int $id ID utilisateur
     * @return bool True si suppression réussie, false sinon
     */
    public function deleteUser(int $id): bool
    {
        return $this->delete($id);
    }

    /**
     * Hydrate un objet UserDTO à partir d'un tableau SQL
     *
     * Conversion array (mutable) → DTO (immutable)
     *
     * @param array<string,mixed> $data Ligne SQL brute
     * @return UserDTO Objet DTO immutable
     */
    private function hydrate(array $data): UserDTO
    {
        return new UserDTO(
            id: (int) $data['id'],
            username: (string) $data['username'],
            passwordHash: (string) $data['password_hash'],  // ✅ Nom cohérent avec le DTO
            role: (string) $data['role'],
            email: (string) $data['email'],
            createdAt: isset($data['created_at']) ? (string) $data['created_at'] : null,
        );
    }
}
