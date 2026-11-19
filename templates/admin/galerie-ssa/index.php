<?php
/**
 * Galerie (App\Galerie\GalerieAdmin) : envoi groupé en haut, puis toutes les photos dans l'ordre du site, chacune avec
 * sa description, son retrait et ses flèches (boutons formaction dans le même formulaire, sans formulaire imbriqué).
 * @var list<array<string, mixed>> $photos @var list<string> $accueil @var int $sansDescription
 */
use App\Admin\Admin;
use App\Admin\Ui;
use App\Core\Csrf;
use App\Site\Vignette;

$base = Admin::url('galerie/');
$n = count($photos);
?>
<header class="adm-entete-page">
  <div class="adm-entete-page__textes">
    <h1 class="adm-entete-page__titre">Galerie</h1>
    <p class="adm-entete-page__sous-titre">Les photos de la page Galerie, 24 par page, dans l'ordre ci-dessous. <?= $n ?> photo<?= $n > 1 ? 's' : '' ?><?= $sansDescription > 0 ? ', dont ' . $sansDescription . ' sans description' : '' ?>.</p>
  </div>
</header>
<form class="adm-carte adm-carte--simple" method="post" action="<?= e($base . 'envoi/') ?>" enctype="multipart/form-data">
  <?= Csrf::field() ?>
  <h2 class="adm-carte__titre">Ajouter des photos</h2>
  <div class="adm-grille-champs">
    <div class="adm-champ adm-champ--pile">
      <label for="g-photos">Photos</label>
      <p class="adm-champ__aide" id="g-photos-aide">Plusieurs à la fois (40 au plus par envoi) : JPG, PNG ou WebP, 15 Mo au plus chacune. Converties en WebP.</p>
      <input type="file" id="g-photos" name="photos[]" multiple accept="image/png,image/jpeg,image/webp" aria-describedby="g-photos-aide" required>
    </div>
    <fieldset class="adm-champ adm-champ--pile">
      <legend>Place dans la galerie</legend>
      <label class="adm-case"><input type="radio" name="place" value="debut" checked> Au début (les plus récentes d'abord)</label>
      <label class="adm-case"><input type="radio" name="place" value="fin"> À la fin</label>
    </fieldset>
  </div>
  <div class="adm-rangee"><button class="btn" type="submit"><?= Ui::icon('upload') ?>Envoyer les photos</button></div>
</form>
<?php if ($photos === []): ?>
<p class="adm-carte adm-carte--simple adm-carte__vide">Aucune photo pour l'instant.</p>
<?php else: ?>
<form method="post" action="<?= e($base) ?>">
  <?= Csrf::field() ?>
  <ul class="ssa-galerie" role="list">
    <?php foreach ($photos as $i => $p): $pid = (int) $p['id']; $surAccueil = in_array((string) $p['path'], $accueil, true); ?>
    <li class="ssa-galerie__photo" id="photo-<?= $pid ?>">
      <img src="<?= e(Vignette::chemin((string) $p['path'], 400)) ?>" alt="" width="400" height="300" loading="lazy" decoding="async">
      <div class="adm-champ adm-champ--pile">
        <label for="alt-<?= $pid ?>">Description de la photo <?= $i + 1 ?></label>
        <input id="alt-<?= $pid ?>" name="alt[<?= $pid ?>]" maxlength="250" value="<?= e((string) $p['alt']) ?>">
      </div>
      <div class="ssa-galerie__actions">
        <?php if ($surAccueil): ?><span class="pastille pastille--point pastille--info">Sur l'accueil</span>
        <?php else: ?><label class="adm-case"><input type="checkbox" name="retirer[]" value="<?= $pid ?>"> Retirer</label><?php endif ?>
        <span class="ssa-galerie__fleches">
          <?php if ($i > 0): ?><button class="btn btn--discret" type="submit" formaction="<?= e($base . 'deplacer/') ?>" name="photo" value="<?= $pid ?>:haut"><?= Ui::icon('up') ?><span class="adm-masque">Avancer la photo <?= $i + 1 ?></span></button><?php endif ?>
          <?php if ($i < $n - 1): ?><button class="btn btn--discret" type="submit" formaction="<?= e($base . 'deplacer/') ?>" name="photo" value="<?= $pid ?>:bas"><?= Ui::icon('down') ?><span class="adm-masque">Reculer la photo <?= $i + 1 ?></span></button><?php endif ?>
        </span>
      </div>
    </li>
    <?php endforeach ?>
  </ul>
  <div class="adm-pied-form ssa-galerie__pied"><button class="btn" type="submit">Enregistrer les descriptions et les retraits</button></div>
</form>
<?php endif ?>
