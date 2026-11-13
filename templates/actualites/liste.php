<?php
/** Actualités (maquette docs/maquettes/actualites.md). @var array $t @var string $path @var list<array> $articles @var int $page @var int $pages */
partial('site/haut', ['chemin' => $path, 'titre' => $t['titre'], 'chapo' => $t['chapo']]);
?>
<section class="section">
  <div class="container">
    <?php if ($articles === []): ?>
    <p class="etat-vide"><?= e(t('actualites.aucune')) ?></p>
    <?php else: ?>
    <ul class="cartes-articles"><?php foreach ($articles as $a) partial('actualites/carte', ['a' => $a]) ?></ul>
    <?php endif ?>
    <?php if ($pages > 1): ?>
    <nav class="pagination" aria-label="<?= e(t('site.pagination')) ?>">
      <ul>
        <?php for ($n = 1; $n <= $pages; $n++): ?>
        <li><a href="<?= e(lurl('/actualites/')) ?><?= $n > 1 ? '?page=' . $n : '' ?>"<?= $n === $page ? ' aria-current="page"' : '' ?>><?= $n ?></a></li>
        <?php endfor ?>
      </ul>
    </nav>
    <?php endif ?>
  </div>
</section>
