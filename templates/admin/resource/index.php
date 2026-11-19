<?php
/**
 * Liste d'une ressource. Avec « list_group » (nom d'un champ select), une carte par option, chacune avec son « Ajouter ».
 * Filtres instantanés (au-delà de 5 fiches) : champs select et publication, seulement s'ils départagent la liste.
 * @var string $key @var array<string, mixed> $res @var list<array<string, mixed>> $rows
 */
use App\Admin\Ui;

$groupField = $res['list_group'] ?? null;
$groups = $groupField !== null ? (array) $res['fields'][$groupField]['options'] : ['' => ''];
$singular = (string) $res['singular'];
$plural = (string) ($res['plural'] ?? (str_ends_with($singular, 's') || str_ends_with($singular, 'x') ? $singular : $singular . 's'));
$article = !empty($res['feminine']) ? 'une' : 'un';

$filters = [];
$filterKeys = [];
foreach ($res['fields'] as $name => $f) {
    if ($f['type'] === 'select' && $name !== $groupField) {
        $filterKeys[$name] = [$name, static fn ($v): string => (string) $v];
        $filters[$name] = [(string) $f['label'], array_map('strval', $f['options'])];
    } elseif ($name === 'is_published') {
        $filterKeys['statut'] = [$name, static fn ($v): string => (int) $v === 1 ? 'en-ligne' : 'masque'];
        $filters['statut'] = ['Sur le site', ['en-ligne' => 'Affiché', 'masque' => 'Masqué']];
    }
}
foreach ($filterKeys as $fk => [$col, $map]) {
    $seen = array_unique(array_map(static fn (array $r): string => $map($r[$col] ?? null), $rows));
    if (count($seen) < 2) {
        unset($filters[$fk], $filterKeys[$fk]);
    } else {
        $filters[$fk][1] = array_intersect_key($filters[$fk][1], array_flip($seen));
    }
}
?>
<header class="adm-entete-page">
  <div class="adm-entete-page__textes">
    <h1 class="adm-entete-page__titre"><?= e((string) $res['label']) ?></h1>
    <?php if ($res['intro'] !== ''): ?><p class="adm-entete-page__sous-titre"><?= e((string) $res['intro']) ?></p><?php endif ?>
  </div>
  <?php if ($groupField === null || !empty($res['export'])): ?>
  <div class="adm-entete-page__actions">
    <?php if (!empty($res['export'])) { $adresse = (string) $res['export']; $quoi = Ui::count(count($rows), $singular, $plural); require ROOT . '/templates/admin/partials/exporter.php'; } // export : ajout du projet SSA ?>
    <?php if ($groupField === null): ?><a class="btn" href="/admin/<?= e($key) ?>/nouveau" data-modal><?= Ui::icon('plus') ?>Ajouter <?= $article ?> <?= e($singular) ?></a><?php endif ?>
  </div>
  <?php endif ?>
</header>

<div data-list>
  <?php if (count($rows) > 5) { $searchLabel = 'Rechercher'; $countWords = [$singular, $plural]; $total = count($rows); require ROOT . '/templates/admin/partials/filters.php'; } ?>
  <?php foreach ($groups as $group => $groupLabel): $items = array_values(array_filter($rows, static fn (array $r): bool => $groupField === null || (string) $r[$groupField] === (string) $group)); ?>
  <section class="adm-carte" data-filter-grid aria-label="<?= e($groupLabel !== '' ? (string) $groupLabel : (string) $res['label']) ?>">
    <?php if ($groupField !== null): ?>
    <div class="adm-carte__entete">
      <h2 class="adm-carte__titre"><?= e((string) $groupLabel) ?> <span class="adm-compteur adm-compteur--neutre"><?= count($items) ?></span></h2>
      <a class="btn" href="/admin/<?= e($key) ?>/nouveau?<?= e(http_build_query([$groupField => $group])) ?>" data-modal><?= Ui::icon('plus') ?>Ajouter<span class="adm-masque"> : <?= e((string) $groupLabel) ?></span></a>
    </div>
    <?php endif ?>
    <?php if ($items === []): ?>
    <p class="adm-carte__vide">Aucun<?= $article === 'une' ? 'e' : '' ?> <?= e($singular) ?> pour l'instant.<?php if ($groupField === null): ?> <a href="/admin/<?= e($key) ?>/nouveau" data-modal>Ajouter <?= $article ?> <?= e($singular) ?></a><?php endif ?></p>
    <?php else: $moveTab = ''; require __DIR__ . '/rows.php'; endif ?>
  </section>
  <?php endforeach ?>
  <div class="adm-carte adm-carte--simple adm-etat adm-etat--compact" data-filter-empty hidden><p class="adm-etat__titre">Aucun résultat</p><p class="adm-etat__texte">Aucune fiche ne correspond à la recherche ou aux filtres.</p><button class="btn btn--neutre" type="button" data-filter-reset>Effacer les filtres</button></div>
</div>
