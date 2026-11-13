<?php
declare(strict_types=1);

/*
 * Reprise des contenus du site actuel dans la base (décision du 2026-11-13) : lit docs/source/existant/contenus.json
 * (produit par scripts/extraire-site-actuel.mjs) et copie les images sous public/media/.
 * Refuse une base qui contient déjà des contenus, sauf avec --remplacer (vide d'abord les tables reprises).
 * Les vidéos ne sont pas copiées : elles sont réencodées par le module vidéo (chemins d'origine gardés dans videos).
 *   php scripts/importer-contenus.php [--remplacer]
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require __DIR__ . '/../app/bootstrap.php';

use App\Core\Db;

const SOURCE = ROOT . '/docs/source/existant';
const CIBLE = ROOT . '/public/media';
// Catégories de l'agenda du site actuel (libellé affiché => clé).
const CATEGORIES = ['Collectif' => 'collectif', 'Expérimentateurs' => 'experimentateurs', 'Pour tous' => 'pour_tous'];

function slug(string $texte): string
{
    $t = transliterator_transliterate('Any-Latin; Latin-ASCII; Lower()', $texte) ?: strtolower($texte);
    return trim((string) preg_replace('/[^a-z0-9]+/', '-', $t), '-');
}

/** Slug unique dans la table : suffixe -2, -3… en cas de doublon. */
function slugUnique(string $table, string $base, array &$pris): string
{
    $s = $base !== '' ? $base : 'page';
    for ($n = 2; isset($pris[$table][$s]); $n++) {
        $s = $base . '-' . $n;
    }
    $pris[$table][$s] = true;
    return $s;
}

/** Copie un média de l'ancien site sous public/media/<dossier>/ et renvoie son chemin public. */
function copier(string $src, string $dossier): string
{
    $depuis = SOURCE . '/medias' . $src;
    if (!is_file($depuis)) {
        throw new RuntimeException("Média absent de la source : $src (relancer scripts/extraire-site-actuel.mjs)");
    }
    $nom = basename($src);
    is_dir(CIBLE . '/' . $dossier) || mkdir(CIBLE . '/' . $dossier, 0775, true);
    copy($depuis, CIBLE . '/' . $dossier . '/' . $nom) || throw new RuntimeException("Copie impossible : $src");
    return '/media/' . $dossier . '/' . $nom;
}

/** Espaces en double et retours chariot : nettoyés, rien d'autre n'est réécrit. */
function propre(string $t): string
{
    return trim((string) preg_replace(['/\r\n?/', '/[ \t]{2,}/'], ["\n", ' '], $t));
}

$contenus = json_decode((string) file_get_contents(SOURCE . '/contenus.json'), true, 512, JSON_THROW_ON_ERROR);
$tables = ['article_images', 'articles', 'events', 'partenaires', 'galerie_photos'];
$deja = array_sum(array_map(static fn (string $t): int => (int) Db::run("SELECT COUNT(*) FROM $t")->fetchColumn(), $tables));
if ($deja > 0 && !in_array('--remplacer', $argv, true)) {
    fwrite(STDERR, "La base contient déjà $deja contenu(s) : rien n'est importé (--remplacer pour tout reprendre).\n");
    exit(1);
}

$maintenant = date('Y-m-d H:i:s');
$pris = [];
$bilan = Db::transaction(static function () use ($contenus, $tables, $maintenant, &$pris): array {
    foreach ($tables as $t) {
        Db::run("DELETE FROM $t");
    }

    $nArticles = 0;
    foreach ($contenus['articles'] as $a) {
        $slug = slugUnique('articles', slug($a['titre']), $pris);
        $images = array_map(static fn (array $i): string => copier($i['src'], 'actualites/' . $slug), $a['images']);
        $id = Db::insert(
            'INSERT INTO articles (titre, resume, description, date_article, image, image_alt, is_published, created_at, slug, lieu, heure, liens, videos, ancien_numero)
             VALUES (?, ?, ?, ?, ?, ?, 1, ?, ?, ?, ?, ?, ?, ?)',
            [
                propre($a['titre']), propre($a['resume']), propre($a['description']), $a['date'],
                $images[0] ?? null, '', $maintenant, $slug, propre($a['lieu']) ?: null, $a['heure'] ?: null,
                $a['liens'] ? json_encode($a['liens'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
                $a['videos'] ? json_encode($a['videos'], JSON_UNESCAPED_SLASHES) : null,
                $a['ancien_numero'],
            ],
        );
        foreach (array_slice($images, 1) as $pos => $chemin) {
            Db::run('INSERT INTO article_images (article_id, path, alt, position) VALUES (?, ?, ?, ?)', [$id, $chemin, '', $pos]);
        }
        $nArticles++;
    }

    foreach ($contenus['evenements'] as $e) {
        Db::run(
            'INSERT INTO events (title, starts_at, ends_at, address, description, is_published, created_at, category, slug, links)
             VALUES (?, ?, ?, ?, ?, 1, ?, ?, ?, ?)',
            [
                propre($e['titre']), substr($e['debut'], 0, 16), substr($e['fin'], 0, 16), propre($e['lieu']) ?: null,
                propre(trim($e['description'] . "\n" . $e['infos'])) ?: null, $maintenant,
                CATEGORIES[$e['categorie'] ?? ''] ?? null, slugUnique('events', slug($e['titre']), $pris), $e['liens'] ?: null,
            ],
        );
    }

    foreach (['partenaire' => $contenus['partenaires'], 'financeur' => $contenus['financeurs']] as $groupe => $logos) {
        foreach ($logos as $pos => $l) {
            Db::run('INSERT INTO partenaires (nom, url, logo, groupe, position) VALUES (?, ?, ?, ?, ?)', [$l['nom'], $l['url'], copier($l['logo'], 'partenaires'), $groupe, $pos]);
        }
    }

    foreach ($contenus['galerie'] as $pos => $src) {
        Db::run('INSERT INTO galerie_photos (path, alt, position, created_at) VALUES (?, ?, ?, ?)', [copier($src, 'galerie'), '', $pos, $maintenant]);
    }

    return [$nArticles, count($contenus['evenements']), count($contenus['partenaires']) + count($contenus['financeurs']), count($contenus['galerie'])];
});

[$na, $ne, $np, $ng] = $bilan;
fwrite(STDOUT, "Importé : $na articles, $ne événements, $np partenaires et financeurs, $ng photos de galerie.\n");
