<?php
/**
 * Script de migration pour modifier la contrainte de durée des événements
 * Permet des événements jusqu'à 30 jours au lieu de 8 heures
 * 
 * Usage:
 *   php migrations/apply_duration_fix.php
 */

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

// Configuration de la base de données
$dbType = $_ENV['DB_TYPE'] ?? 'sqlite';
$dbPath = $_ENV['DB_PATH'] ?? __DIR__ . '/../data/db.sqlite';
$dbHost = $_ENV['DB_HOST'] ?? 'localhost';
$dbName = $_ENV['DB_NAME'] ?? 'ssa_capsule';
$dbUser = $_ENV['DB_USER'] ?? 'root';
$dbPass = $_ENV['DB_PASS'] ?? '';

echo "🔧 Application de la migration pour permettre les événements multi-jours...\n\n";

if ($dbType === 'sqlite') {
    echo "📦 Base de données: SQLite\n";
    echo "📍 Chemin: $dbPath\n\n";
    
    if (!file_exists($dbPath)) {
        die("❌ Erreur: La base de données SQLite n'existe pas: $dbPath\n");
    }
    
    try {
        $pdo = new PDO("sqlite:$dbPath");
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // SQLite ne supporte pas ALTER TABLE pour modifier les CHECK constraints
        // Il faut recréer la table
        echo "⚠️  SQLite nécessite de recréer la table (les données seront préservées)...\n";
        
        // 1. Créer une table temporaire avec la nouvelle contrainte
        $pdo->exec("
            CREATE TABLE agenda_events_new (
                id               INTEGER PRIMARY KEY AUTOINCREMENT,
                title            TEXT NOT NULL,
                starts_at        TEXT NOT NULL,
                duration_minutes INTEGER NOT NULL CHECK (duration_minutes BETWEEN 30 AND 43200),
                location         TEXT,
                color            TEXT NOT NULL DEFAULT '#3788d8',
                description      TEXT,
                created_by       INTEGER,
                created_at       TEXT NOT NULL DEFAULT (CURRENT_TIMESTAMP),
                updated_at       TEXT,
                FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
            )
        ");
        
        // 2. Copier les données
        $pdo->exec("
            INSERT INTO agenda_events_new 
            SELECT * FROM agenda_events
        ");
        
        // 3. Supprimer l'ancienne table
        $pdo->exec("DROP TABLE agenda_events");
        
        // 4. Renommer la nouvelle table
        $pdo->exec("ALTER TABLE agenda_events_new RENAME TO agenda_events");
        
        // 5. Recréer les index
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_agenda_starts_at ON agenda_events(starts_at)");
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_agenda_created_by ON agenda_events(created_by)");
        
        echo "✅ Migration SQLite appliquée avec succès!\n";
        
    } catch (PDOException $e) {
        die("❌ Erreur SQLite: " . $e->getMessage() . "\n");
    }
    
} elseif ($dbType === 'mysql' || $dbType === 'mariadb') {
    echo "📦 Base de données: MySQL/MariaDB\n";
    echo "📍 Serveur: $dbHost\n";
    echo "📍 Base: $dbName\n\n";
    
    try {
        $dsn = "mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4";
        $pdo = new PDO($dsn, $dbUser, $dbPass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // MySQL/MariaDB supporte ALTER TABLE pour modifier les CHECK constraints
        echo "🔄 Modification de la contrainte CHECK...\n";
        
        // Supprimer l'ancienne contrainte
        try {
            $pdo->exec("ALTER TABLE agenda_events DROP CONSTRAINT ck_agenda_duration");
        } catch (PDOException $e) {
            // La contrainte peut ne pas exister ou avoir un nom différent
            echo "⚠️  Contrainte existante non trouvée (peut être normal)\n";
        }
        
        // Ajouter la nouvelle contrainte
        $pdo->exec("
            ALTER TABLE agenda_events 
            ADD CONSTRAINT ck_agenda_duration 
            CHECK (duration_minutes BETWEEN 30 AND 43200)
        ");
        
        echo "✅ Migration MySQL/MariaDB appliquée avec succès!\n";
        
    } catch (PDOException $e) {
        die("❌ Erreur MySQL/MariaDB: " . $e->getMessage() . "\n");
    }
    
} else {
    die("❌ Type de base de données non supporté: $dbType\n");
}

echo "\n✨ La contrainte de durée a été mise à jour: 30 minutes à 30 jours (au lieu de 8 heures)\n";
echo "📝 Les utilisateurs peuvent maintenant créer des événements multi-jours.\n";

