<?php
/** Étape 1 : e-mail et mot de passe. @var string|null $error @var string $email */
use App\Admin\AdminController as A;
use App\Core\Csrf;
?>
<p class="cnx-carte__etape">Étape 1 sur 2</p>
<h1 class="cnx-carte__titre">Connexion</h1>
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
  <a class="adm-lien cnx-lien" href="<?= e(A::url('mot-de-passe-oublie/')) ?>">Mot de passe oublié</a>
  <a class="adm-lien cnx-lien" href="/">Retour au site</a>
</nav>
