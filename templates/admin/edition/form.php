<?php
/**
 * Fiche d'édition des rubriques écrites pour le projet (actualités, agenda), sur le modèle de admin/resource/form.php
 * (modules/crud-declaratif) : groupes de champs titrés à gauche, publication, lien vers la page publique et suppression
 * à droite. Champs rendus par templates/admin/partials/field.php.
 * @var string $title @var string $retour adresse de la liste @var string $retourLibelle @var string $action envoi du formulaire
 * @var array<string, list<string>> $groupes titre du groupe => noms des champs @var array<string, array<string, mixed>> $champs
 * @var array<string, mixed> $valeurs @var array<string, string> $erreurs @var ?string $suppression adresse de suppression (fiche existante)
 * @var ?string $voir adresse publique @var string $nomFiche @var bool $fichiers formulaire avec envoi de fichiers
 * @var string $apres HTML déjà échappé posé sous les groupes (médias de la fiche)
 */
use App\Admin\Ui;
use App\Core\Csrf;

$suppression = $suppression ?? null;
$voir = $voir ?? null;
$apres = $apres ?? '';
$fichiers = !empty($fichiers);
// Publication : les cases qui ne sont rangées dans aucun groupe (une case d'un groupe, comme « Photo décorative », reste à sa place).
$rangees = array_merge(...array_values($groupes));
$publication = array_filter($champs, static fn (array $f, string $nom): bool => $f['type'] === 'checkbox' && !in_array($nom, $rangees, true), ARRAY_FILTER_USE_BOTH);
$key = '';
?>
<header class="adm-entete-page">
  <div class="adm-entete-page__textes">
    <a class="adm-retour" href="<?= e($retour) ?>"><?= Ui::icon('back') ?><?= e($retourLibelle) ?></a>
    <h1 class="adm-entete-page__titre"><?= e($title) ?></h1>
  </div>
</header>
<?php if ($erreurs !== []): ?>
<div class="adm-alerte adm-alerte--erreur" role="alert"><?= Ui::icon('alert') ?><p><strong>Erreur :</strong> rien n'a été enregistré. Corrigez <?= count($erreurs) > 1 ? 'les ' . count($erreurs) . ' champs signalés' : 'le champ signalé' ?>.</p></div>
<?php endif ?>
<?php /* Contenu à gauche ; à droite, colonne collante : publication, enregistrement, puis suppression (demande de Kouzia,
   2026-11-20). Les contrôles de droite appartiennent au formulaire par l'attribut form="fiche". */ ?>
<div class="adm-colonnes adm-colonnes--edition">
  <form class="adm-colonnes__principal" id="fiche" method="post" action="<?= e($action) ?>"<?= $fichiers ? ' enctype="multipart/form-data"' : '' ?> novalidate>
    <?= Csrf::field() ?>
    <?php $formulaire = ''; foreach ($groupes as $titreGroupe => $noms): $idGroupe = 'g-' . substr(md5($titreGroupe), 0, 6); ?>
    <section class="adm-carte adm-carte--simple" aria-labelledby="<?= $idGroupe ?>">
      <h2 class="adm-carte__titre" id="<?= $idGroupe ?>"><?= e($titreGroupe) ?></h2>
      <div class="adm-grille-champs">
        <?php foreach ($noms as $name) { $f = $champs[$name]; $value = $valeurs[$name] ?? ''; $error = $erreurs[$name] ?? null; $values = $valeurs; require ROOT . '/templates/admin/partials/field.php'; } ?>
      </div>
    </section>
    <?php endforeach ?>
    <?= $apres ?>
  </form>
  <aside class="adm-colonnes__cote" aria-label="Publication et enregistrement">
    <section class="adm-carte adm-carte--simple adm-publier" aria-labelledby="publi-titre">
      <h2 class="adm-carte__titre" id="publi-titre">Publication</h2>
      <?php $formulaire = 'fiche'; foreach ($publication as $name => $f) { $value = $valeurs[$name] ?? ($f['default'] ?? 0); $error = $erreurs[$name] ?? null; $values = $valeurs; require ROOT . '/templates/admin/partials/field.php'; } $formulaire = ''; ?>
      <?php if ($voir !== null): ?><a class="adm-lien adm-publier__voir" href="<?= e($voir) ?>" target="_blank" rel="noopener"><?= Ui::icon('external') ?>Voir sur le site<span class="adm-masque"> (nouvel onglet)</span></a><?php endif ?>
      <div class="adm-publier__actions">
        <button class="btn" type="submit" form="fiche">Enregistrer</button>
        <a class="btn btn--neutre" href="<?= e($retour) ?>">Annuler</a>
      </div>
    </section>
    <?php if ($suppression !== null): ?>
    <form class="adm-supprimer" method="post" action="<?= e($suppression) ?>" data-confirm="Supprimer « <?= e($nomFiche) ?> » ? Cette action est définitive.">
      <?= Csrf::field() ?>
      <?php if ($publication !== []): ?><p class="adm-supprimer__texte">Pour retirer la fiche du site sans la perdre, désactivez plutôt « <?= e((string) reset($publication)['label']) ?> ».</p><?php endif ?>
      <button class="btn btn--danger btn--secondaire" type="submit"><?= Ui::icon('trash') ?>Supprimer définitivement</button>
    </form>
    <?php endif ?>
  </aside>
</div>
