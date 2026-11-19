<?php
/**
 * Avertissement d'expiration de session, à placer juste avant </body> d'un gabarit de page connectée
 * (coquille d'administration, espace membre) : <?php partial('session/minuteur') ?>
 * Rien n'est rendu sans session valide. Le dialogue est fermé tant que le script ne l'ouvre pas.
 * Les textes changeants (décompte, état expiré) sont écrits par le script (textContent).
 */
use App\Core\Csrf;
use App\Session\Minuteur;

$etat = Minuteur::courant();
if ($etat['statut'] !== 'valide') {
    return;
}
$r = Minuteur::reglages();
$retour = Minuteur::cheminLocal((string) ($_SERVER['REQUEST_URI'] ?? '/')) ?? '/';
?>
<div class="minuteur" data-minuteur
  data-restant="<?= (int) $etat['avant_inactivite'] ?>" data-absolu="<?= (int) $etat['avant_absolue'] ?>"
  data-inactivite="<?= (int) $r['inactivite'] ?>" data-avertir="<?= (int) $r['avertir'] ?>"
  data-etat="/session/etat" data-prolonger="/session/prolonger"
  data-expiree="/session/expiree?retour=<?= e(rawurlencode($retour)) ?>"<?= $r['prolonger_sur_activite'] ? ' data-activite' : '' ?>>
  <dialog class="dialogue minuteur__dialogue" id="minuteur-dialogue" aria-labelledby="minuteur-titre" aria-describedby="minuteur-texte" data-minuteur-dialogue>
    <div class="dialogue__entete">
      <h2 class="dialogue__titre" id="minuteur-titre" data-minuteur-titre>Votre session va expirer</h2>
    </div>
    <div class="dialogue__corps">
      <div class="minuteur__compte" aria-hidden="true">
        <svg viewBox="0 0 24 24" focusable="false"><circle cx="12" cy="13" r="8"/><path d="M12 9v4l2.5 1.5M9.5 2.5h5M12 2.5V5"/></svg>
        <span data-minuteur-horloge>2:00</span>
      </div>
      <p id="minuteur-texte" data-minuteur-texte>Votre session expire dans <strong data-minuteur-reste>2 min</strong>. Sans action de votre part, vous serez déconnecté&nbsp;; votre saisie en cours sera gardée sur cet appareil.</p>
      <p class="minuteur__erreur" role="alert" data-minuteur-erreur hidden></p>
      <p class="visually-hidden" role="status" data-minuteur-annonce></p>
    </div>
    <form class="dialogue__actions" method="post" action="/session/prolonger" data-minuteur-form>
      <?= Csrf::field() ?>
      <input type="hidden" name="retour" value="<?= e($retour) ?>">
      <?php if ($r['deconnexion'] !== ''): ?>
      <button class="btn btn--secondaire" type="submit" formaction="<?= e($r['deconnexion']) ?>" data-minuteur-sortir>Se déconnecter</button>
      <?php endif ?>
      <a class="btn btn--secondaire" href="<?= e($r['connexion']) ?>?retour=<?= e(rawurlencode($retour)) ?>" data-minuteur-reconnecter hidden>Se reconnecter</a>
      <button class="btn" type="submit" autofocus data-minuteur-prolonger>Rester connecté</button>
    </form>
  </dialog>
  <p class="visually-hidden" role="status" data-minuteur-statut></p>
</div>
