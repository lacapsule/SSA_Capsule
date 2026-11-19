<?php
/**
 * Lignes d'une liste de fiches : vignette, nom, détails, état, flèches d'ordre, « Modifier ».
 * @var string $key @var array<string, mixed> $res @var list<array<string, mixed>> $items @var string $moveTab
 * @var array<string, array{0: string, 1: callable}> $filterKeys clé de filtre => [colonne, valeur affichée]
 */
use App\Admin\Ui;

$first = (string) array_key_first($res['columns']);
$imageCol = null;
foreach ($res['columns'] as $col => $label) {
    if (($res['fields'][$col]['type'] ?? '') === 'image') {
        $imageCol = (string) $col;
    }
}
$skip = [$first, $imageCol, 'is_published', $res['tabs'] ?? null, $res['list_group'] ?? null];
$sortable = isset($res['sortable']) && count($items) > 1;
?>
<ul class="adm-lignes" role="list">
  <?php foreach ($items as $n => $row): $rowName = (string) ($row[$first] ?? ''); $href = '/admin/' . $key . '/' . (int) $row['id']; ?>
  <?php
    $details = [];
    foreach ($res['columns'] as $col => $label) {
        $v = $row[$col] ?? null;
        if (in_array($col, $skip, true) || $v === null || $v === '') {
            continue;
        }
        $f = $res['fields'][$col] ?? ['type' => 'text'];
        $details[] = match ($f['type']) {
            'select' => (string) ($f['options'][$v] ?? $v),
            'date' => $label . ' : ' . date('d/m/Y', (int) strtotime((string) $v)),
            'checkbox' => (int) $v === 1 ? (string) $label : '',
            'url' => (string) preg_replace('#^https?://(www\.)?#', '', rtrim((string) $v, '/')),
            default => (string) $v,
        };
    }
    $details = array_filter($details, static fn (string $d): bool => $d !== '');
  ?>
  <li class="" id="ligne-<?= (int) $row['id'] ?>" data-filter-row<?php foreach ($filterKeys as $fk => [$col, $map]): ?> data-f-<?= e($fk) ?>="<?= e($map($row[$col] ?? null)) ?>"<?php endforeach ?>>
    <?php if ($imageCol !== null): ?>
    <span class="adm-lignes__vignette"><?php if ($row[$imageCol]): ?><img src="<?= e((string) $row[$imageCol]) ?>" alt="" loading="lazy" decoding="async"><?php endif ?></span>
    <?php endif ?>
    <a class="adm-lignes__principal" href="<?= e($href) ?>" data-modal>
      <span class="adm-lignes__titre"><?= e($rowName !== '' ? $rowName : 'Sans nom') ?></span>
      <?php if ($details !== []): ?><span class="adm-meta"><?= e(implode(' · ', $details)) ?></span><?php endif ?>
    </a>
    <?php if (isset($row['is_published']) && (int) $row['is_published'] !== 1): ?><span class="pastille pastille--point pastille--neutre">Masqué</span><?php endif ?>
    <?php if (isset($res['geocode']) && ($row['latitude'] ?? null) === null): ?><span class="pastille pastille--point pastille--attention">Pas sur la carte</span><?php endif ?>
    <?php if ($sortable) { $isFirst = $n === 0; $isLast = $n === count($items) - 1; require ROOT . '/templates/admin/partials/move.php'; } ?>
    <a class="btn btn--neutre" href="<?= e($href) ?>" data-modal><?= Ui::icon('edit') ?>Modifier<span class="adm-masque"> <?= e($rowName) ?></span></a>
  </li>
  <?php endforeach ?>
</ul>
