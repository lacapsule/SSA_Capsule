<?php
/**
 * Pagination en vrais liens : page précédente, numéros, page suivante (rule web-seo, liens dans le HTML).
 * @var string $base adresse de la première page (ex. /galerie/) @var int $page @var int $pages
 */
if ($pages <= 1) {
    return;
}
$lien = static fn (int $n): string => lurl($base) . ($n > 1 ? '?page=' . $n : '');
$fleche = static fn (string $d): string => '<svg viewBox="0 0 16 16" width="16" height="16" aria-hidden="true" focusable="false"><path d="' . $d . '" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>';
?>
<nav class="pagination" aria-label="<?= e(t('site.pagination')) ?>">
  <ul>
    <?php if ($page > 1): ?><li><a class="pagination__sens" href="<?= e($lien($page - 1)) ?>" rel="prev"><?= $fleche('M13 8H3M7 4L3 8l4 4') ?><?= e(t('site.page_precedente')) ?></a></li><?php endif ?>
    <?php for ($n = 1; $n <= $pages; $n++): ?>
    <li><a href="<?= e($lien($n)) ?>"<?= $n === $page ? ' aria-current="page"' : '' ?>><span class="visually-hidden"><?= e(t('site.page')) ?> </span><?= $n ?></a></li>
    <?php endfor ?>
    <?php if ($page < $pages): ?><li><a class="pagination__sens" href="<?= e($lien($page + 1)) ?>" rel="next"><?= e(t('site.page_suivante')) ?><?= $fleche('M3 8h10M9 4l4 4-4 4') ?></a></li><?php endif ?>
  </ul>
</nav>
