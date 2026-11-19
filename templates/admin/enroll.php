<?php
/** Enrôlement de la double authentification. @var string $secret @var string $uri @var string $issuer @var string|null $error */
use App\Admin\AdminController as A;
use App\Core\Csrf;
?>
<h1 class="cnx-carte__titre">Double authentification</h1>
<p class="cnx-carte__intro">Une protection en plus du mot de passe : sans votre téléphone, personne ne peut entrer, même avec le mot de passe.</p>
<ol class="auth-pas">
  <li>
    <h2>Installez une application d'authentification</h2>
    <p class="adm-champ__aide">Par exemple Aegis, FreeOTP, Google Authenticator ou Microsoft Authenticator, gratuites.</p>
  </li>
  <li>
    <h2>Scannez ce QR code avec l'application</h2>
    <div class="auth-qr">
      <div class="auth-qr-image" data-otpauth="<?= e($uri) ?>" role="img" aria-label="QR code de configuration pour <?= e($issuer) ?>"></div>
      <div>
        <p class="adm-champ__aide">Impossible de scanner ? Saisissez cette clé dans l'application :</p>
        <p class="auth-cle"><?= e($secret) ?></p>
      </div>
    </div>
  </li>
  <li>
    <h2><label for="e-code">Saisissez le code affiché par l'application</label></h2>
    <form class="cnx-form" method="post" action="<?= e(A::url('double-authentification/')) ?>" novalidate>
      <?= Csrf::field() ?>
      <?php if ($error): ?><p class="adm-alerte adm-alerte--erreur" role="alert" id="enroll-erreur"><?= e($error) ?></p><?php endif ?>
      <div class="adm-champ">
        <input id="e-code" name="code" class="adm-saisie auth-code" inputmode="numeric" pattern="[0-9]*" autocomplete="one-time-code" maxlength="6" data-chiffres required<?= $error ? ' aria-invalid="true" aria-describedby="enroll-erreur"' : '' ?>>
      </div>
      <button class="btn btn--plein" type="submit">Activer la double authentification</button>
    </form>
  </li>
</ol>
