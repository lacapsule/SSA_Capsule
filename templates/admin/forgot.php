<?php
/** Mot de passe oublié. @var bool $sent */
use App\Admin\AdminAuth;
use App\Admin\AdminController as A;
use App\Core\Csrf;
?>
<?php if ($sent): ?>
  <h1 class="cnx-carte__titre">Regardez vos e-mails</h1>
  <p class="cnx-carte__intro" role="status">Si un compte administrateur existe avec cette adresse, un lien pour choisir un nouveau mot de passe vient de partir. Il est valable <?= AdminAuth::RESET_MINUTES ?> minutes et ne sert qu'une fois. Pensez à regarder les indésirables.</p>
  <a class="btn btn--neutre btn--plein" href="<?= e(A::url('connexion/')) ?>">Retour à la connexion</a>
<?php else: ?>
  <h1 class="cnx-carte__titre">Mot de passe oublié ?</h1>
  <p class="cnx-carte__intro">Indiquez l'adresse e-mail de votre compte : vous recevrez un lien pour choisir un nouveau mot de passe. La double authentification restera demandée.</p>
  <form class="cnx-form" method="post" action="<?= e(A::url('mot-de-passe-oublie/')) ?>" novalidate>
    <?= Csrf::field('admin-forgot') ?>
    <div class="adm-champ">
      <label for="f-email">Adresse e-mail</label>
      <input class="adm-saisie" id="f-email" name="email" type="email" autocomplete="username" required autofocus>
    </div>
    <button class="btn btn--plein" type="submit">Recevoir le lien</button>
  </form>
  <nav class="cnx-carte__bas" aria-label="Autres actions">
  <a class="adm-lien cnx-lien" href="<?= e(A::url('connexion/')) ?>">Retour à la connexion</a>
</nav>
<?php endif ?>
