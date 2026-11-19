<?php
/** Confirmation de restauration sans JavaScript (même formulaire que le dialogue de l'écran). Variables : $nom */

use App\Admin\Admin;
use App\Core\Csrf;
?>
<div class="adm-page svc">
  <a class="adm-retour" href="<?= e(Admin::url('sauvegardes/')) ?>">Sauvegardes</a>
  <form class="adm-carte adm-carte--simple adm-carte--danger" method="post" action="<?= e(Admin::url('sauvegardes/restaurer/')) ?>">
    <?= Csrf::field('sauvegarde') ?>
    <input type="hidden" name="fichier" value="<?= e($nom) ?>">
    <h1 class="adm-carte__titre">Restaurer cette sauvegarde&#8239;?</h1>
    <p class="adm-carte__intro">La base actuelle sera remplacée. Tout ce qui a été saisi depuis cette date sera perdu ; une sauvegarde de sécurité est faite juste avant.</p>
    <dl class="adm-definitions"><dt>Sauvegarde</dt><dd class="adm-mono"><?= e($nom) ?></dd></dl>
    <div class="adm-champ">
      <label class="adm-champ__libelle" for="svc-saisie">Pour confirmer, saisissez <strong>RESTAURER</strong></label>
      <input class="adm-saisie" id="svc-saisie" type="text" name="confirmation" autocomplete="off" spellcheck="false" required pattern="RESTAURER">
    </div>
    <div class="adm-pied-form">
      <a class="btn btn--neutre" href="<?= e(Admin::url('sauvegardes/')) ?>">Annuler</a>
      <button class="btn btn--danger" type="submit">Restaurer</button>
    </div>
  </form>
</div>
