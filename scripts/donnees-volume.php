<?php
declare(strict_types=1);

/*
 * Jeux de données de développement pour regarder les listes à tous les volumes (skill web-ui-states,
 * « Listes à tous les volumes ») : agenda, actualités, logos. Jamais en production.
 * Écrit data/volume-<nom>.sqlite à partir de data/dev.sqlite (vide : 0 élément ; un : 1 ; max : volume maximal probable).
 * Servir un jeu : DB_DSN=sqlite:data/volume-max.sqlite php -S 127.0.0.1:8093 -t public public/router.php
 *   php scripts/donnees-volume.php
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
$racine = dirname(__DIR__);
require $racine . '/app/Core/Env.php';
App\Core\Env::load($racine . '/.env');
if (($_ENV['APP_ENV'] ?? 'local') === 'production') {
    fwrite(STDERR, "Refusé : jeux de test interdits en production.\n");
    exit(1);
}
$source = $racine . '/data/dev.sqlite';
if (!is_file($source)) {
    fwrite(STDERR, "data/dev.sqlite absent : make db puis php scripts/importer-contenus.php.\n");
    exit(1);
}

// Volume maximal probable : 12 événements à venir et 30 passés (un an d'expérimentation, réunions et ateliers
// mensuels), 40 articles, 14 logos.
$volumes = ['vide' => [0, 0, 0, 0], 'un' => [1, 0, 1, 1], 'max' => [12, 30, 40, 14]];
$fuseau = new DateTimeZone('Europe/Paris');
foreach ($volumes as $nom => [$aVenir, $passes, $articles, $logos]) {
    $cible = $racine . "/data/volume-$nom.sqlite";
    copy($source, $cible) || throw new RuntimeException("Copie impossible : $cible");
    $db = new PDO('sqlite:' . $cible, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $db->exec('DELETE FROM article_images; DELETE FROM articles; DELETE FROM events; DELETE FROM partenaires; DELETE FROM traductions');
    $maintenant = new DateTimeImmutable('now', $fuseau);
    $ev = $db->prepare('INSERT INTO events (title, starts_at, ends_at, address, description, is_published, created_at, category, slug) VALUES (?, ?, ?, ?, ?, 1, ?, ?, ?)');
    $cats = ['collectif', 'experimentateurs', 'pour_tous', null];
    for ($i = 1; $i <= $aVenir + $passes; $i++) {
        $debut = $i <= $aVenir ? $maintenant->modify("+$i week")->setTime(18, 0) : $maintenant->modify('-' . ($i - $aVenir) . ' week')->setTime(14, 30);
        $titre = ($i <= $aVenir ? 'Événement à venir de test ' : 'Événement passé de test ') . $i . ($i % 3 === 0 ? ' avec un titre nettement plus long que les autres pour voir le retour à la ligne' : '');
        $ev->execute([$titre, $debut->format('Y-m-d H:i'), $debut->modify('+2 hours')->format('Y-m-d H:i'), $i % 2 ? 'Salle de test, Morlaix' : null,
            $i % 4 === 0 ? '' : str_repeat('Texte de test pour mesurer la hauteur de la carte. ', 1 + $i % 4), $maintenant->format('Y-m-d H:i:s'), $cats[$i % 4], "evenement-test-$i"]);
    }
    $ar = $db->prepare('INSERT INTO articles (titre, resume, description, date_article, image, image_alt, is_published, created_at, slug) VALUES (?, ?, ?, ?, ?, ?, 1, ?, ?)');
    $photos = glob($racine . '/public/media/actualites/*/*.webp') ?: [];
    for ($i = 1; $i <= $articles; $i++) {
        $photo = $photos && $i % 5 ? '/media/actualites/' . basename(dirname($photos[$i % count($photos)])) . '/' . basename($photos[$i % count($photos)]) : null;
        $ar->execute(['Article de test ' . $i . ($i % 4 === 0 ? ' dont le titre court sur plusieurs lignes dans la carte' : ''), 'Résumé de test ' . $i . '.', 'Texte de test.',
            $maintenant->modify("-$i day")->format('Y-m-d'), $photo, '', $maintenant->format('Y-m-d H:i:s'), "article-test-$i"]);
    }
    $lo = $db->prepare('INSERT INTO partenaires (nom, url, logo, groupe, position, hauteur_rem) VALUES (?, ?, ?, ?, ?, ?)');
    $fichiers = glob($racine . '/public/media/partenaires/*.webp') ?: [];
    for ($i = 1; $i <= $logos && $fichiers; $i++) {
        $lo->execute(["Partenaire de test $i", 'https://exemple.org/', '/media/partenaires/' . basename($fichiers[$i % count($fichiers)]), $i % 3 ? 'partenaire' : 'financeur', $i, 3]);
    }
    fwrite(STDOUT, "data/volume-$nom.sqlite : $aVenir à venir, $passes passés, $articles articles, $logos logos.\n");
}
