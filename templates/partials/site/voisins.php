<?php
/**
 * Fiches voisines (reprise demandée par Kouzia le 2026-11-17) : deux cartes, précédente et suivante, avec la photo de la
 * fiche ou, sans photo (événements), sa date en grand ; titre, date ; puis le retour à la rubrique en bouton.
 * @var ?array{titre: string, chemin: string, vignette?: ?string, date?: string} $precedent @var ?array{titre: string, chemin: string, vignette?: ?string, date?: string} $suivant
 * @var array{chemin: string, libelle: string} $rubrique @var string $nature article ou evenement (intitulés)
 */
use App\Site\Dates;

$fleche = static fn (string $d): string => '<svg viewBox="0 0 16 16" width="18" height="18" aria-hidden="true" focusable="false"><path d="' . $d . '" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>';
$carte = static function (?array $f, string $sens) use ($nature, $fleche): void {
    if ($f === null) {
        return;
    }
    $date = (string) ($f['date'] ?? '');
    $p = $date !== '' ? Dates::pastille($date) : null;
    $vignette = (string) ($f['vignette'] ?? '');
    ?>
    <li class="voisin voisin--<?= e($sens) ?>">
      <?php if ($vignette !== ''): ?><img class="voisin__visuel" src="<?= e($vignette) ?>" alt="" width="400" height="250" loading="lazy" decoding="async">
      <?php elseif ($p !== null): ?><span class="voisin__visuel voisin__date" aria-hidden="true"><strong><?= e($p['jour']) ?></strong><span><?= e($p['mois']) ?> <?= e(substr($date, 0, 4)) ?></span></span><?php endif ?>
      <div class="voisin__texte">
        <p class="voisin__sens"><?= $sens === 'precedent' ? $fleche('M10 3.5 5.5 8l4.5 4.5') : '' ?><?= e(t('site.' . $sens . '_' . $nature)) ?><?= $sens === 'suivant' ? $fleche('M6 3.5 10.5 8 6 12.5') : '' ?></p>
        <a class="voisin__titre" href="<?= e(lurl($f['chemin'])) ?>" rel="<?= $sens === 'precedent' ? 'prev' : 'next' ?>"><?= e($f['titre']) ?></a>
        <?php if ($date !== ''): ?><time class="voisin__quand" datetime="<?= e(Dates::iso($date)) ?>"><?= e(Dates::longue($date)) ?></time><?php endif ?>
      </div>
    </li>
<?php };
?>
<nav class="section voisins" aria-label="<?= e(t('site.voisins_' . $nature)) ?>">
  <div class="container">
    <?php if ($precedent !== null || $suivant !== null): ?>
    <ul class="voisins__liste<?= $precedent === null || $suivant === null ? ' voisins__liste--seul' : '' ?>">
      <?php $carte($precedent, 'precedent'); $carte($suivant, 'suivant'); ?>
    </ul>
    <?php endif ?>
    <p class="voisins__retour"><a class="button button--ghost" href="<?= e(lurl($rubrique['chemin'])) ?>"><?= e($rubrique['libelle']) ?></a></p>
  </div>
</nav>
