<?php
/**
 * Actualités, piste 2 « modernisée » (composition de l'accueil, docs/maquettes/pistes/2-modernisee/) : la plus récente
 * en grand, photo et texte côte à côte, puis les autres en grille de cartes ; pages suivantes en vrais liens.
 * @var array $t @var string $path @var list<array> $articles @var int $page @var int $pages
 */
use App\Site\Dates;
use App\Site\Texte;
use App\Site\Vignette;

partial('site/haut', ['chemin' => $path, 'titre' => $t['titre'], 'chapo' => $t['chapo']]);
$une = $page === 1 && $articles !== [] ? array_shift($articles) : null;
?>
<section class="section">
  <div class="container">
    <?php if ($une === null && $articles === []): ?>
    <p class="etat-vide"><?= e(t('actualites.aucune')) ?></p>
    <?php endif ?>
    <?php if ($une !== null): $poster = json_decode((string) ($une['videos'] ?? ''), true)[0]['poster'] ?? null; $vignette = (string) ($une['image'] ?: $poster); ?>
    <article class="actu-une<?= $vignette === '' ? ' actu-une--sans-photo' : '' ?>">
      <?php if ($vignette !== ''): [$ul, $uh] = Vignette::taille($vignette) ?? [1280, 960]; // même cadre que la photo d'un article, taille réelle ?><figure class="article-media actu-une__media"><img class="actu-une__photo" <?= Vignette::attributs($vignette, '(max-width: 52rem) 100vw, 44rem', 960) ?> alt="" width="<?= $ul ?>" height="<?= $uh ?>" fetchpriority="high" decoding="async"><?php if ($poster !== null && empty($une['image'])): ?><span class="pastille-video"><svg viewBox="0 0 16 16" width="14" height="14" aria-hidden="true" focusable="false"><path d="M4 2.5v11l9-5.5z" fill="currentColor"/></svg><?= e(t('actualites.video')) ?></span><?php endif ?></figure><?php endif ?>
      <div class="actu-une__corps">
        <p class="sur-titre"><?= e(t('actualites.a_la_une')) ?></p>
        <h2 class="actu-une__titre"><a href="<?= e(lurl('/actualites/' . $une['slug'] . '/')) ?>"<?= langue_attr($une, 'titre') ?>><?= e((string) $une['titre']) ?></a></h2>
        <time class="actu-une__date" datetime="<?= e(Dates::iso((string) $une['date_article'])) ?>"><?= e(Dates::longue((string) $une['date_article'])) ?></time>
        <?php if (!empty($une['resume'])): ?><p<?= langue_attr($une, 'resume') ?>><?= e(Texte::couper((string) $une['resume'], 240)) ?></p><?php endif ?>
        <span class="lien-fleche" aria-hidden="true"><?= e(t('actualites.lire')) ?><svg viewBox="0 0 16 16" width="16" height="16" focusable="false"><path d="M3 8h10M9 4l4 4-4 4" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
      </div>
    </article>
    <?php endif ?>
    <?php if ($articles !== []): ?>
    <?php // Articles rangés par année, l'année en intertitre (revue visuelle du 2026-11-17 : casser la longue grille).
    $parAnnee = [];
    foreach ($articles as $a) {
        $parAnnee[substr((string) $a['date_article'], 0, 4)][] = $a;
    }
    foreach ($parAnnee as $annee => $liste): ?>
    <h2 class="actualites__annee" id="annee-<?= e((string) $annee) ?>"><?= e((string) $annee) ?></h2>
    <ul class="cartes-articles" aria-labelledby="annee-<?= e((string) $annee) ?>"><?php foreach ($liste as $a) partial('actualites/carte', ['a' => $a]) ?></ul>
    <?php endforeach ?>
    <?php endif ?>
    <?php partial('site/pagination', ['base' => '/actualites/', 'page' => $page, 'pages' => $pages]) ?>
  </div>
</section>
