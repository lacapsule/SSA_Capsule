<?php
/**
 * Fiche d'une ressource : champs à gauche, publication et suppression à droite. S'ouvre aussi dans la fenêtre
 * du tableau de bord (data-modal-content).
 * @var string $title @var string $key @var array<string, mixed> $res @var ?array $row @var array<string, mixed> $values @var array<string, string> $errors
 */
use App\Admin\Ui;
use App\Core\Csrf;

$formId = 'fiche';
$main = array_filter($res['fields'], static fn (array $f): bool => $f['type'] !== 'checkbox');
$side = array_filter($res['fields'], static fn (array $f): bool => $f['type'] === 'checkbox');
$recordName = $row ? (string) ($row[array_key_first($res['columns'])] ?? '') : '';
$heading = $recordName !== '' ? $recordName : $title;
$hasFile = array_filter($res['fields'], static fn (array $f): bool => in_array($f['type'], ['image', 'pdf'], true)) !== [];
?>
<header class="adm-entete-page">
  <div class="adm-entete-page__textes">
    <a class="adm-retour" href="/admin/<?= e($key) ?>"><?= Ui::icon('back') ?><?= e((string) $res['label']) ?></a>
    <h1 class="adm-entete-page__titre"><?= e($heading) ?></h1>
  </div>
</header>
<div data-modal-content data-modal-title="<?= e($heading) ?>">
<?php if ($errors !== []): ?>
<div class="adm-alerte adm-alerte--erreur" role="alert"><?= Ui::icon('alert') ?><p><strong>Erreur :</strong> la fiche n'a pas été enregistrée. Corrigez <?= count($errors) > 1 ? 'les ' . count($errors) . ' champs signalés' : 'le champ signalé' ?>.</p></div>
<?php endif ?>
<div class="adm-colonnes">
  <form class="adm-colonnes__principal" id="<?= $formId ?>" method="post" action="/admin/<?= e($key) ?><?= $row ? '/' . (int) $row['id'] : '' ?>"<?= $hasFile ? ' enctype="multipart/form-data"' : '' ?> novalidate>
    <?= Csrf::field() ?>
    <section class="adm-carte adm-carte--simple" aria-labelledby="infos-titre">
      <h2 class="adm-carte__titre" id="infos-titre">Informations</h2>
      <div class="adm-grille-champs">
        <?php foreach ($main as $name => $f) { $value = $values[$name] ?? ''; $error = $errors[$name] ?? null; require ROOT . '/templates/admin/partials/field.php'; } ?>
      </div>
    </section>
    <?php if ($side !== []): ?>
    <section class="adm-carte adm-carte--simple" aria-labelledby="publi-titre">
      <h2 class="adm-carte__titre" id="publi-titre"><?= isset($side['is_published']) ? 'Publication' : 'Options' ?></h2>
      <?php foreach ($side as $name => $f) { $value = $values[$name] ?? ''; $error = $errors[$name] ?? null; require ROOT . '/templates/admin/partials/field.php'; } ?>
    </section>
    <?php endif ?>
    <div class="adm-rangee">
      <button class="btn" type="submit">Enregistrer</button>
      <a class="btn btn--neutre" href="/admin/<?= e($key) ?>" data-modal-close>Annuler</a>
    </div>
  </form>
  <?php if ($row): ?>
  <aside class="adm-colonnes__cote" aria-label="Suppression">
    <form class="adm-carte adm-carte--simple adm-zone-danger" method="post" action="/admin/<?= e($key) ?>/<?= (int) $row['id'] ?>/suppression" data-confirm="Supprimer « <?= e($recordName) ?> » ? Cette action est définitive.">
      <?= Csrf::field() ?>
      <h2 class="adm-carte__titre">Supprimer</h2>
      <?php if (isset($side['is_published'])): ?><p>Pour retirer la fiche du site sans la perdre, désactivez plutôt « <?= e((string) $side['is_published']['label']) ?> ».</p><?php endif ?>
      <button class="btn btn--danger btn--secondaire" type="submit"><?= Ui::icon('trash') ?>Supprimer définitivement</button>
    </form>
  </aside>
  <?php endif ?>
</div>
</div>
