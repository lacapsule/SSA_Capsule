<?php
/**
 * Photos sans description (App\Galerie\DescriptionsAdmin) : une photo en grand, son champ à côté ; douze par écran.
 * @var list<array{cle: string, src: string, origine: string, lien: string, article: bool}> $photos
 * @var int $total @var int $page @var int $pages
 */
use App\Admin\Admin;
use App\Admin\Ui;
use App\Core\Csrf;
use App\Site\Vignette;

$base = Admin::url('photos-a-decrire/');
$debut = ($page - 1) * App\Galerie\DescriptionsAdmin::PAR_ECRAN;
?>
<header class="adm-entete-page">
  <div class="adm-entete-page__textes">
    <a class="adm-retour" href="<?= e(Admin::url('galerie/')) ?>"><?= Ui::icon('back') ?>Galerie</a>
    <h1 class="adm-entete-page__titre">Photos sans description</h1>
    <p class="adm-entete-page__sous-titre">Une phrase par photo, lue à la place de l'image par les personnes qui ne la voient pas et par les moteurs de recherche. Dites ce qu'elle montre (qui, quoi, où) sans commencer par « Photo de ».</p>
  </div>
</header>
<?php if ($total === 0): ?>
<div class="adm-carte adm-carte--simple adm-etat">
  <p class="adm-etat__titre">Toutes les photos ont une description</p>
  <p class="adm-etat__texte">Les photos envoyées plus tard apparaîtront ici tant qu'elles ne sont pas décrites.</p>
  <a class="btn btn--neutre" href="<?= e(Admin::url('galerie/')) ?>">Retour à la galerie</a>
</div>
<?php else: ?>
<form class="adm-carte ssa-decrire" method="post" action="<?= e($base) ?>">
  <?= Csrf::field() ?>
  <input type="hidden" name="page" value="<?= $page ?>">
  <div class="adm-carte__entete">
    <p class="adm-carte__titre"><?= e(Ui::count($total, 'photo à décrire', 'photos à décrire')) ?></p>
    <?php if ($pages > 1): ?><p class="adm-meta">Photos <?= $debut + 1 ?> à <?= min($debut + count($photos), $total) ?></p><?php endif ?>
  </div>
  <ol class="ssa-decrire__liste" role="list">
    <?php foreach ($photos as $i => $p): $id = 'd-' . $p['cle']; ?>
    <li class="ssa-decrire__photo">
      <img src="<?= e(Vignette::chemin($p['src'], 640)) ?>" alt="" width="640" height="480" loading="<?= $i < 2 ? 'eager' : 'lazy' ?>" decoding="async">
      <div class="ssa-decrire__champs">
        <div class="adm-champ adm-champ--pile">
          <div class="adm-champ__tete">
            <label for="<?= e($id) ?>">Description de la photo <?= $debut + $i + 1 ?></label>
            <p class="adm-champ__aide" id="<?= e($id) ?>-aide"><?= e($p['origine']) ?> · <a class="adm-lien" href="<?= e($p['lien']) ?>">voir la fiche</a></p>
          </div>
          <textarea id="<?= e($id) ?>" name="alt[<?= e($p['cle']) ?>]" rows="2" maxlength="250" aria-describedby="<?= e($id) ?>-aide"></textarea>
        </div>
        <?php if ($p['article']): ?><label class="adm-case"><input type="checkbox" name="decorative[]" value="<?= e($p['cle']) ?>"> Photo décorative (aucune information à transmettre)</label><?php endif ?>
      </div>
    </li>
    <?php endforeach ?>
  </ol>
  <div class="adm-pied-form ssa-galerie__pied">
    <button class="btn" type="submit">Enregistrer les descriptions</button>
    <?php if ($page < $pages): ?><a class="btn btn--neutre" href="<?= e($base . '?page=' . ($page + 1)) ?>">Passer à la suite<?= Ui::icon('arrow') ?></a><?php endif ?>
    <?php if ($page > 1): ?><a class="adm-lien" href="<?= e($base . ($page > 2 ? '?page=' . ($page - 1) : '')) ?>">Photos précédentes</a><?php endif ?>
  </div>
</form>
<?php endif ?>
