<?php
/**
 * Barre de filtres instantanés (admin.js du tableau de bord, initFilters). Masquée sans JavaScript : la liste complète reste lisible.
 * @var array<string, array{0: string, 1: array<string, string>}> $filters clé => [libellé, [valeur => libellé]]
 * @var string $searchLabel @var array{0: string, 1: string} $countWords @var int $total
 */
use App\Admin\Ui;

$fid = 'flt-' . substr(md5($searchLabel . implode(',', array_keys($filters))), 0, 6);
?>
<div class="adm-filtres" data-filters hidden>
  <div class="adm-champ adm-champ--pile adm-champ--recherche">
    <label for="<?= $fid ?>-q"><?= e($searchLabel) ?></label>
    <div class="adm-champ-recherche"><?= Ui::icon('search') ?><input id="<?= $fid ?>-q" type="search" autocomplete="off" data-filter-q></div>
  </div>
  <?php foreach ($filters as $fkey => [$flabel, $foptions]): ?>
  <div class="adm-champ adm-champ--pile">
    <label for="<?= $fid ?>-<?= e($fkey) ?>"><?= e($flabel) ?></label>
    <select id="<?= $fid ?>-<?= e($fkey) ?>" data-filter="<?= e($fkey) ?>">
      <option value="">Tous</option>
      <?php foreach ($foptions as $fv => $fl): ?><option value="<?= e((string) $fv) ?>"><?= e($fl) ?></option><?php endforeach ?>
    </select>
  </div>
  <?php endforeach ?>
  <button class="btn btn--discret" type="button" data-filter-reset hidden><?= Ui::icon('close') ?>Effacer<span class="adm-masque"> les filtres</span></button>
  <p class="adm-filtres__compte" data-filter-count data-one="<?= e($countWords[0]) ?>" data-many="<?= e($countWords[1]) ?>" aria-live="polite"><?= e(Ui::count($total, $countWords[0], $countWords[1])) ?></p>
</div>
