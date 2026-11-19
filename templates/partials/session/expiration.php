<?php
/**
 * Message d'expiration sur la page de connexion (réglage avis = 'partiel'), affiché une seule fois :
 * <?php partial('session/expiration') ?> en tête du formulaire de connexion.
 * Avec avis = 'flash', le message passe par Session::flash et ce partiel n'affiche rien.
 * @var string|null $classe classe du message (par défaut 'alerte alerte--info minuteur-avis' ; back-office : 'adm-alerte')
 */
$message = App\Session\Minuteur::messageExpiration();
if ($message === null) {
    return;
}
$classe = isset($classe) && is_string($classe) ? $classe : 'alerte alerte--info minuteur-avis';
?>
<div class="<?= e($classe) ?>" role="status">
  <svg class="alerte__icone" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.25" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><circle cx="12" cy="13" r="8"/><path d="M12 9v4l2.5 1.5M9.5 2.5h5M12 2.5V5"/></svg>
  <div class="alerte__corps">
    <p class="alerte__titre"><span class="visually-hidden">Information : </span>Session expirée</p>
    <p class="alerte__texte"><?= e($message) ?></p>
  </div>
</div>
