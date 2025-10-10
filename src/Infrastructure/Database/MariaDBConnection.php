<?php

declare(strict_types=1);

namespace Capsule\Infrastructure\Database;

use PDO;
use PDOException;
use RuntimeException;

/**
 * Singleton pour la connexion PDO à une base MariaDB/MySQL.
 *
 * - Utilise une configuration par défaut modifiable via setConfig().
 * - Gère l'instanciation unique PDO avec paramètres sécurisés.
 * - Lance une exception RuntimeException en cas d'échec de connexion.
 */
class MariaDBConnection
{
    /**
     * Instance unique PDO.
     *
     * @var PDO|null
     */
    private static ?PDO $pdo = null;

    /**
     * Configuration de connexion (valeurs par défaut).
     *
     * @var array<string, mixed>
     */
    private static array $config = [
        'host' => 'db',      // Hôte de la base, important pour Docker ou environnement local
        'dbname' => 'ssa_dev',
        'user' => 'admin',
        'pass' => 'admin',
        'port' => 3306,
        'charset' => 'utf8mb4',
    ];

    /**
     * Met à jour la configuration de connexion.
     * Réinitialise la connexion PDO pour appliquer la nouvelle config.
     *
     * @param array<string, mixed> $conf Tableau associatif des paramètres à remplacer
     * @return void
     */
    public static function setConfig(array $conf): void
    {
        self::$config = array_merge(self::$config, $conf);
        self::$pdo = null; // Reset de la connexion PDO pour forcer réinitialisation
    }

    /**
     * Retourne l'instance PDO unique (singleton).
     * Si la connexion n’existe pas, l’instancie avec la configuration courante.
     *
     * @throws RuntimeException Si la connexion échoue
     * @return PDO Instance PDO active
     */
    public static function getInstance(): PDO
    {
        if (self::$pdo === null) {
            $dsn = sprintf(
                'mysql:host=%s;port=%d;dbname=%s;charset=%s',
                self::$config['host'],
                self::$config['port'],
                self::$config['dbname'],
                self::$config['charset']
            );
            try {
                self::$pdo = new PDO($dsn, self::$config['user'], self::$config['pass'], [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]);
            } catch (PDOException $e) {
                throw new RuntimeException('Connexion MySQL échouée : ' . $e->getMessage(), 0, $e);
            }
        }

        return self::$pdo;
    }
}
