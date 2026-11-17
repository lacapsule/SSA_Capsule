<?php
/**
 * Article, piste 2 (maquette docs/maquettes/actualites-fiche.md ; reprise demandée par Kouzia le 2026-11-17) : en-tête
 * de magazine (titre, chapô, date, heure et lieu ; à droite la photo ou la vidéo, dans le même cadre pour tous), puis une
 * seule colonne de lecture centrée : le texte, les liens dessous ; la galerie de l'article ; les fiches voisines.
 * @var array $a @var string $path @var list<array> $images @var list<array> $liens @var list<array> $videos
 */
use App\Site\Dates;
use App\Site\Texte;
use App\Site\Vignette;

$infos = [['icone' => 'M8 3v3M16 3v3M4 9h16M5 5h14a1 1 0 0 1 1 1v13a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1z', 'html' => '<span><time datetime="' . e(Dates::iso((string) $a['date_article'])) . '">' . e(Dates::longue((string) $a['date_article'])) . '</time>']];
if (!empty($a['heure'])) {
    // Date et heure sur une ligne : « samedi 31 janvier 2026, 9 h » (heure saisie « 09:00 » ou « 9h30 »).
    $heure = preg_match('/^(\d{1,2})\s*[:hH]\s*(\d{2})?$/', trim((string) $a['heure']), $m) === 1 ? (int) $m[1] . ' h' . (isset($m[2]) && $m[2] !== '00' && $m[2] !== '' ? ' ' . $m[2] : '') : trim((string) $a['heure']);
    $infos[0]['html'] .= ', ' . e($heure);
}
$infos[0]['html'] .= '</span>';
if (!empty($a['lieu'])) {
    $infos[] = ['icone' => 'M12 21s-7-6.2-7-11.5A7 7 0 0 1 19 9.5C19 14.8 12 21 12 21zM12 12a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5z', 'html' => '<span' . langue_attr($a, 'lieu') . '>' . e((string) $a['lieu']) . '</span>'];
}
// Média de l'article (photo ou vidéo : jamais les deux) toujours à la même place, à droite du titre, dans un même cadre
// (demande de Kouzia, 2026-11-17 : une mise en page identique d'un article à l'autre). Photo jamais agrandie.
ob_start();
if ($videos !== []):
    $v = $videos[0]; ?>
    <figure class="article-media article-media--video"><video controls preload="none" playsinline poster="<?= e(Vignette::chemin((string) $v['poster'], 960)) ?>" width="1280" height="720"><source src="<?= e((string) $v['src']) ?>" type="video/mp4"></video></figure>
<?php elseif (!empty($a['image'])):
    [$pl, $ph] = Vignette::taille((string) $a['image']) ?? [1600, 1200]; ?>
    <figure class="article-media"><img <?= Vignette::attributs((string) $a['image'], '(max-width: 52rem) 100vw, 42rem', 960) ?> alt="<?= e((string) ($a['image_alt'] ?? '')) ?>" width="<?= $pl ?>" height="<?= $ph ?>" fetchpriority="high" decoding="async"></figure>
<?php endif;
$media = (string) ob_get_clean();
partial('site/haut', ['chemin' => $path, 'titre' => (string) $a['titre'], 'chapo' => (string) $a['resume'], 'parent' => ['chemin' => '/actualites/', 'libelle' => t('menu.actualites')], 'infos' => $infos, 'cote' => $media, 'classe' => 'page-head--article']);
?>
<article class="section article">
  <div class="container">
    <div class="article__corps">
      <div class="article__texte prose"<?= langue_attr($a, 'description') ?>><?= Texte::article((string) $a['description']) ?></div>
      <?php if ($liens !== []): $sites = (array) \App\Site\Site::get('noms_sites', []); // nom lisible du site de chaque lien ?>
      <section class="article__liens" aria-labelledby="liens-titre">
        <h2 id="liens-titre"><?= e(t('actualites.liens')) ?></h2>
        <ul class="article__liens-liste">
          <?php foreach ($liens as $l): $hote = (string) preg_replace('/^www\./', '', (string) parse_url((string) $l['url'], PHP_URL_HOST)); $site = (string) ($sites[$hote] ?? ($l['libelle'] ?: $hote)); ?><li><?php $interne = str_starts_with((string) $l['url'], '/'); // lien vers une page du site (carte des lieux) ?><a class="article__lien" href="<?= e($interne ? lurl((string) $l['url']) : (string) $l['url']) ?>"<?= $interne ? '' : ' rel="noopener"' ?>><span class="article__lien-site"><?= e($site) ?></span><span class="article__lien-texte"><?= e($l['texte'] !== '' ? (string) $l['texte'] : t('actualites.lien_ouvrir')) ?></span><svg viewBox="0 0 16 16" width="16" height="16" aria-hidden="true" focusable="false"><path d="M3 8h10M9 4l4 4-4 4" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></a></li><?php endforeach ?>
        </ul>
      </section>
      <?php endif ?>
    </div>
    <?php if ($images !== []): ?>
    <ul class="galerie-article"><?php foreach ($images as $i): ?><li><a href="<?= e((string) $i['path']) ?>"><img src="<?= e(Vignette::chemin((string) $i['path'], 640)) ?>" alt="<?= e((string) $i['alt']) ?>" loading="lazy" decoding="async"></a></li><?php endforeach ?></ul>
    <?php endif ?>
  </div>
</article>
<?php
$vignette = static function (?array $x): ?string {
    $chemin = $x === null ? null : ($x['image'] ?: (json_decode((string) ($x['videos'] ?? ''), true)[0]['poster'] ?? null));
    return $chemin === null || $chemin === '' ? null : \App\Site\Vignette::chemin((string) $chemin, 400);
};
partial('site/voisins', [
    'nature' => 'article',
    'precedent' => $precedent ? ['titre' => $precedent['titre'], 'chemin' => '/actualites/' . $precedent['slug'] . '/', 'vignette' => $vignette($precedent), 'date' => (string) $precedent['date_article']] : null,
    'suivant' => $suivant ? ['titre' => $suivant['titre'], 'chemin' => '/actualites/' . $suivant['slug'] . '/', 'vignette' => $vignette($suivant), 'date' => (string) $suivant['date_article']] : null,
    'rubrique' => ['chemin' => '/actualites/', 'libelle' => t('accueil.toutes_actualites')],
]);
