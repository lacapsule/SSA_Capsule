<?php

declare(strict_types=1);

namespace Capsule\Infrastructure\Database;

use PDO;
use PDOException;
use RuntimeException;

/**
 * Gestionnaire centralisé pour la connexion PDO à une base SQLite.
 *
 * Fournit une unique instance PDO configurée avec les bons attributs (sécurité, mode de fetch, etc.).
 */
class SqliteConnection
{
    /**
     * Instance unique partagée (singleton).
     *
     * @var PDO|null
     */
    private static ?PDO $pdo = null;

    /**
     * Chemin relatif à la racine du projet.
     *
     * @var string
     */

    private static string $relativePath = '/data/database.sqlite';

    /**
     * Retourne l’instance PDO SQLite configurée.
     *
     * @return PDO
     *
     * @throws RuntimeException Si la connexion échoue.
     */
    public static function getInstance(): PDO
    {
        if (self::$pdo === null) {
            $absolutePath = dirname(__DIR__, 3) . self::$relativePath;
            try {
                self::$pdo = new PDO('sqlite:' . $absolutePath, null, null, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]);
            } catch (PDOException $e) {
                throw new RuntimeException('Impossible de se connecter à la base SQLite : ' . $e->getMessage(), 0, $e);
            }
        }

        return self::$pdo;
    }

    /**
     * Modifie le chemin de la base de données SQLite.
     * À appeler avant le premier `getInstance()`.
     *
     * @param string $relativePath Chemin relatif à `/src/../`
     *
     * @return void
     */
    public static function setPath(string $relativePath): void
    {
        self::$relativePath = $relativePath;
        self::$pdo = null; // reset l’instance
    }
}
