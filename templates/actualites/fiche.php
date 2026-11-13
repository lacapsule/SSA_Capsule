<?php
/** Article (maquette docs/maquettes/actualites-fiche.md). @var array $a @var string $path @var list<array> $images @var list<array> $liens @var list<string> $videos */
use App\Site\Dates;
use App\Site\Texte;

partial('site/haut', ['chemin' => $path, 'titre' => (string) $a['titre'], 'chapo' => (string) $a['resume'], 'parent' => ['chemin' => '/actualites/', 'libelle' => t('menu.actualites')]]);
?>
<article class="section">
  <div class="container prose">
    <p class="article-meta"><time class="pastille" datetime="<?= e(Dates::iso((string) $a['date_article'])) ?>"><?= e(Dates::longue((string) $a['date_article'])) ?></time><?php if (!empty($a['lieu'])): ?> <span<?= langue_attr($a, 'lieu') ?>><?= e((string) $a['lieu']) ?></span><?php endif ?></p>
    <?php if (!empty($a['image'])): ?><img class="article-photo" src="<?= e((string) $a['image']) ?>" alt="<?= e((string) $a['image_alt']) ?>" width="1200" height="800" fetchpriority="high" decoding="async"><?php endif ?>
    <?php foreach ($videos as $v): ?>
    <figure class="article-video"><video controls preload="none" playsinline poster="<?= e((string) $v['poster']) ?>" width="1280" height="720"><source src="<?= e((string) $v['src']) ?>" type="video/mp4"></video></figure>
    <?php endforeach ?>
    <div<?= langue_attr($a, 'description') ?>><?= Texte::paragraphes((string) $a['description']) ?></div>
    <?php if ($images !== []): ?>
    <ul class="galerie-article"><?php foreach ($images as $i): ?><li><a href="<?= e((string) $i['path']) ?>"><img src="<?= e((string) $i['path']) ?>" alt="<?= e((string) $i['alt']) ?>" loading="lazy" decoding="async"></a></li><?php endforeach ?></ul>
    <?php endif ?>
    <?php if ($liens !== []): ?>
    <h2><?= e(t('actualites.liens')) ?></h2>
    <ul class="puces">
      <?php foreach ($liens as $l): ?><li><?= $l['texte'] !== '' ? e($l['texte']) . ' : ' : '' ?><a href="<?= e((string) $l['url']) ?>" rel="noopener"><?= e((string) ($l['libelle'] ?: $l['url'])) ?></a></li><?php endforeach ?>
    </ul>
    <?php endif ?>
  </div>
</article>
<?php
$vignette = static fn (?array $x): ?string => $x === null ? null : ($x['image'] ?: (json_decode((string) ($x['videos'] ?? ''), true)[0]['poster'] ?? null));
partial('site/voisins', [
    'nature' => 'article',
    'precedent' => $precedent ? ['titre' => $precedent['titre'], 'chemin' => '/actualites/' . $precedent['slug'] . '/', 'vignette' => $vignette($precedent)] : null,
    'suivant' => $suivant ? ['titre' => $suivant['titre'], 'chemin' => '/actualites/' . $suivant['slug'] . '/', 'vignette' => $vignette($suivant)] : null,
    'rubrique' => ['chemin' => '/actualites/', 'libelle' => t('accueil.toutes_actualites')],
]);
