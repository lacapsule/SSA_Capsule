<?php
/**
 * Étape 1 : e-mail et mot de passe. « Mot de passe oublié ? » bascule sur place vers le formulaire de demande de lien
 * (admin-auth.js, demande de Kouzia du 2026-11-19) ; sans script, le lien mène à sa page.
 * @var string|null $error @var string $email
 */
use App\Admin\AdminAuth;
use App\Admin\AdminController as A;
use App\Core\Csrf;
?>
<div data-volet="connexion">
<p class="cnx-carte__etape">Étape 1 sur 2</p>
<h1 class="cnx-carte__titre" tabindex="-1">Connexion</h1>
<p class="cnx-carte__intro">Espace de gestion réservé aux administrateurs.</p>
<form class="cnx-form" method="post" action="<?= e(A::url('connexion/')) ?>" novalidate>
  <?= Csrf::field('admin-login') ?>
  <?php if ($error): ?><p class="adm-alerte adm-alerte--erreur" role="alert"><?= e($error) ?></p><?php endif ?>
  <div class="adm-champ">
    <label for="a-email">Adresse e-mail</label>
    <input class="adm-saisie" id="a-email" name="email" type="email" autocomplete="username" value="<?= e($email) ?>" required autofocus>
  </div>
  <?php partial('password', ['id' => 'a-pass', 'name' => 'password', 'label' => 'Mot de passe', 'autocomplete' => 'current-password']) ?>
  <button class="btn btn--plein" type="submit">Continuer</button>
</form>
<nav class="cnx-carte__bas" aria-label="Autres actions">
  <a class="adm-lien cnx-lien" href="<?= e(A::url('mot-de-passe-oublie/')) ?>" data-bascule="oubli">Mot de passe oublié ?</a>
  <a class="adm-lien cnx-lien" href="/">Retour au site</a>
</nav>
</div>
<div data-volet="oubli" hidden>
  <h1 class="cnx-carte__titre" tabindex="-1">Mot de passe oublié ?</h1>
  <p class="cnx-carte__intro">Indiquez l'adresse e-mail de votre compte : vous recevrez un lien pour choisir un nouveau mot de passe, valable <?= AdminAuth::RESET_MINUTES ?> minutes.</p>
  <form class="cnx-form" method="post" action="<?= e(A::url('mot-de-passe-oublie/')) ?>" novalidate>
    <?= Csrf::field('admin-forgot') ?>
    <div class="adm-champ">
      <label for="o-email">Adresse e-mail</label>
      <input class="adm-saisie" id="o-email" name="email" type="email" autocomplete="username" required>
    </div>
    <button class="btn btn--plein" type="submit">Recevoir le lien</button>
  </form>
  <nav class="cnx-carte__bas" aria-label="Autres actions">
    <a class="adm-lien cnx-lien" href="<?= e(A::url('connexion/')) ?>" data-bascule="connexion">Retour à la connexion</a>
  </nav>
</div>
