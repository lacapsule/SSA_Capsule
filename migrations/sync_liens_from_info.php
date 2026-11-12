<?php
/**
 * Remplit le champ "liens" des articles existants à partir des URLs trouvées dans "info".
 * Les liens dans la zone "Liens" deviennent cliquables sur la page article.
 *
 * Usage:
 *   php migrations/sync_liens_from_info.php
 */

declare(strict_types=1);

$configPath = __DIR__ . '/../config/database.php';
if (!is_file($configPath)) {
    die("❌ Fichier config non trouvé: config/database.php\n");
}
$config = require $configPath;
$dsn = $config['dsn'];
$user = $config['user'] ?? null;
$password = $config['password'] ?? null;

if (!is_string($dsn) || $dsn === '') {
    die("❌ Erreur: DSN base de données invalide.\n");
}

echo "🔗 Synchronisation des liens depuis le champ 'info' vers 'liens'…\n\n";

try {
    $pdo = new PDO($dsn, $user, $password, $config['options'] ?? []);
} catch (PDOException $e) {
    die("❌ Connexion impossible: " . $e->getMessage() . "\n");
}

// Vérifier que la colonne liens existe
$stmt = $pdo->query("PRAGMA table_info(articles)");
$columns = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
$hasLiens = false;
foreach ($columns as $col) {
    if (($col['name'] ?? '') === 'liens') {
        $hasLiens = true;
        break;
    }
}
if (!$hasLiens) {
    die("❌ La colonne 'liens' n'existe pas. Exécutez d'abord: sqlite3 ... < migrations/20251224_add_article_liens.sql\n");
}

// Regex pour détecter les URLs dans le texte (http/https)
$urlPattern = '#https?://[^\s<>"\')\]]+#u';

$stmt = $pdo->query("SELECT id, info, liens FROM articles WHERE info IS NOT NULL AND TRIM(info) != ''");
if (!$stmt) {
    die("❌ Impossible de lire les articles.\n");
}

$updated = 0;
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($rows as $row) {
    $id = (int) $row['id'];
    $info = (string) $row['info'];
    $currentLiens = isset($row['liens']) ? trim((string) $row['liens']) : '';

    if (preg_match_all($urlPattern, $info, $matches) === false || empty($matches[0])) {
        continue;
    }

    $urls = array_unique($matches[0]);
    $liensValue = implode("\n", $urls);

    // Ne pas écraser un champ liens déjà rempli par l'utilisateur
    if ($currentLiens !== '') {
        echo "  ⏭ Article #{$id}: 'liens' déjà renseigné, ignoré.\n";
        continue;
    }

    $update = $pdo->prepare("UPDATE articles SET liens = :liens, info = '' WHERE id = :id");
    $update->execute(['liens' => $liensValue, 'id' => $id]);
    if ($update->rowCount() > 0) {
        $updated++;
        echo "  ✅ Article #{$id}: " . count($urls) . " lien(s) copié(s), 'info' vidé.\n";
    }
}

// Vider 'info' pour les articles qui ont déjà des liens (migration antérieure sans vidage)
$stmt2 = $pdo->query("SELECT id FROM articles WHERE (liens IS NOT NULL AND TRIM(liens) != '') AND (info IS NOT NULL AND TRIM(info) != '')");
if ($stmt2) {
    $toClear = $stmt2->fetchAll(PDO::FETCH_COLUMN);
    foreach ($toClear as $id) {
        $pdo->prepare("UPDATE articles SET info = '' WHERE id = ?")->execute([$id]);
        echo "  🧹 Article #{$id}: 'info' vidé (liens déjà présents).\n";
        $updated++;
    }
}

echo "\n✅ Terminé. {$updated} article(s) traités.\n";
