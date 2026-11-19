<?php
/**
 * Liste rangée en onglets : un onglet par option du champ select nommé par « tabs » (motif ARIA tabs, admin.js).
 * Sans JavaScript, les panneaux s'affichent l'un sous l'autre, chacun avec son titre.
 * @var string $key @var array<string, mixed> $res @var list<array<string, mixed>> $rows
 */
use App\Admin\Ui;

$tabField = (string) $res['tabs'];
$options = (array) ($res['fields'][$tabField]['options'] ?? []);
// Option présente dans les données mais retirée de la config : onglet gardé, pour ne perdre aucune fiche.
foreach ($rows as $r) {
    $v = (string) ($r[$tabField] ?? '');
    $options[$v] ??= $v === '' ? 'Sans catégorie' : $v;
}
$slug = static fn (string $v): string => 't-' . (preg_replace('/[^a-z0-9_-]/', '-', strtolower($v)) ?: 'vide');
$active = (string) ($_GET['onglet'] ?? '');
$active = in_array($active, array_map($slug, array_map('strval', array_keys($options))), true) ? $active : $slug((string) array_key_first($options));
$singular = (string) $res['singular'];
$article = !empty($res['feminine']) ? 'une' : 'un';
$filterKeys = [];
?>
<header class="adm-entete-page">
  <div class="adm-entete-page__textes">
    <h1 class="adm-entete-page__titre"><?= e((string) $res['label']) ?></h1>
    <?php if ($res['intro'] !== ''): ?><p class="adm-entete-page__sous-titre"><?= e((string) $res['intro']) ?></p><?php endif ?>
  </div>
  <?php if (!empty($res['export'])): // export tableur : ajout du projet SSA ?>
  <div class="adm-entete-page__actions"><?php $adresse = (string) $res['export']; $quoi = 'toutes les fiches'; require ROOT . '/templates/admin/partials/exporter.php'; ?></div>
  <?php endif ?>
</header>

<div class="adm-onglets-js" data-tabset>
  <div class="adm-onglets-js__liste" role="tablist" aria-label="<?= e((string) $res['fields'][$tabField]['label']) ?>" hidden data-tabset-list>
    <?php foreach ($options as $value => $label): $s = $slug((string) $value); $n = count(array_filter($rows, static fn (array $r): bool => (string) $r[$tabField] === (string) $value)); ?>
    <button class="adm-onglets-js__onglet" type="button" role="tab" id="tab-<?= e($s) ?>" aria-controls="panel-<?= e($s) ?>" aria-selected="<?= $s === $active ? 'true' : 'false' ?>" tabindex="<?= $s === $active ? '0' : '-1' ?>" data-tab="<?= e($s) ?>"><?= e((string) $label) ?> <span class="adm-compteur adm-compteur--neutre"><?= $n ?></span></button>
    <?php endforeach ?>
  </div>
  <?php foreach ($options as $value => $label): $s = $slug((string) $value); $items = array_values(array_filter($rows, static fn (array $r): bool => (string) $r[$tabField] === (string) $value)); ?>
  <section class="adm-onglets-js__panneau" id="panel-<?= e($s) ?>" role="tabpanel" aria-labelledby="tab-<?= e($s) ?>" data-tab-panel="<?= e($s) ?>">
    <h2 class="adm-onglets-js__titre"><?= e((string) $label) ?></h2>
    <div class="adm-carte">
      <div class="adm-carte__entete">
        <p class="adm-carte__titre"><?= e(Ui::count(count($items), $singular)) ?></p>
        <a class="btn" href="/admin/<?= e($key) ?>/nouveau?<?= e(http_build_query([$tabField => $value])) ?>" data-modal><?= Ui::icon('plus') ?>Ajouter<span class="adm-masque"> <?= $article ?> <?= e($singular) ?> : <?= e((string) $label) ?></span></a>
      </div>
      <?php if ($items === []): ?>
      <p class="adm-carte__vide">Aucun<?= $article === 'une' ? 'e' : '' ?> <?= e($singular) ?> dans cet onglet.</p>
      <?php else: $moveTab = $s; require __DIR__ . '/rows.php'; endif ?>
    </div>
  </section>
  <?php endforeach ?>
</div>
