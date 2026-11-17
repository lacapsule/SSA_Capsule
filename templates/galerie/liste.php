<?php
/** Galerie (maquette docs/maquettes/galerie.md), piste 2 : vignettes réduites en colonnes, photo d'origine dans la visionneuse. @var array $t @var string $path @var list<array> $photos @var int $total @var int $page @var int $pages */
partial('site/haut', ['chemin' => $path, 'titre' => $t['titre'], 'chapo' => $t['chapo']]);
?>
<section class="section">
  <div class="container">
    <?php if ($photos === []): ?>
    <p class="etat-vide"><?= e(t('galerie.aucune')) ?></p>
    <?php else: ?>
    <p class="galerie__compte"><?= e(t($pages > 1 ? 'galerie.compte_pages' : 'galerie.compte', ['total' => (string) $total, 'page' => (string) $page, 'pages' => (string) $pages])) ?></p>
    <div class="galerie" data-galerie>
      <ul class="galerie__vignettes">
        <?php foreach ($photos as $i => $p):
            // Texte de remplacement saisi, sinon une description neutre et vraie (jamais vide : le lien en tire son nom).
            $alt = trim((string) $p['alt']) !== '' ? (string) $p['alt'] : t('galerie.photo_n', ['n' => (string) $p['numero'], 'total' => (string) $total]);
        ?>
        <li class="galerie__vignette"><a href="<?= e((string) $p['path']) ?>" class="galerie__ouvrir" data-index="<?= $i ?>"><img <?= \App\Site\Vignette::attributs((string) $p['path'], '(max-width: 36rem) 100vw, (max-width: 56rem) 50vw, 25rem', 640) ?> alt="<?= e($alt) ?>" width="<?= (int) $p['largeur'] ?>" height="<?= (int) $p['hauteur'] ?>" loading="<?= $i < 6 ? 'eager' : 'lazy' ?>" decoding="async"><span class="visually-hidden"> (<?= e(t('galerie.agrandir')) ?>)</span></a></li>
        <?php endforeach ?>
      </ul>
      <dialog class="visionneuse" aria-label="<?= e(t('galerie.visionneuse')) ?>" data-position="<?= e(t('galerie.position')) ?>">
        <div class="visionneuse__barre">
          <p class="visionneuse__position" aria-live="polite" data-position-texte></p>
          <button type="button" class="button button--ghost" data-fermer><?= e(t('galerie.fermer')) ?></button>
        </div>
        <figure class="visionneuse__scene"><figcaption hidden></figcaption></figure>
        <div class="visionneuse__nav">
          <button type="button" class="button button--ghost" data-precedente><?= e(t('galerie.precedente')) ?></button>
          <button type="button" class="button button--ghost" data-suivante><?= e(t('galerie.suivante')) ?></button>
        </div>
      </dialog>
    </div>
    <?php partial('site/pagination', ['base' => '/galerie/', 'page' => $page, 'pages' => $pages]) ?>
    <?php endif ?>
  </div>
</section>
