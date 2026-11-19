<?php
/** Nouveau mot de passe par le lien reçu. @var string $token @var bool $valid @var array<string, string> $errors */
use App\Admin\AdminAuth;
use App\Admin\AdminController as A;
use App\Core\Csrf;

$expired = !$valid || isset($errors['token']);
?>
<?php if ($expired): ?>
  <h1 class="cnx-carte__titre">Lien expiré</h1>
  <p class="cnx-carte__intro">Ce lien n'est plus valable : il ne sert qu'une fois, a une durée limitée et est remplacé par toute nouvelle demande.</p>
  <a class="btn btn--plein" href="<?= e(A::url('mot-de-passe-oublie/')) ?>">Demander un nouveau lien</a>
  <nav class="cnx-carte__bas" aria-label="Autres actions">
  <a class="adm-lien cnx-lien" href="<?= e(A::url('connexion/')) ?>">Retour à la connexion</a>
</nav>
<?php else: ?>
  <h1 class="cnx-carte__titre">Nouveau mot de passe</h1>
  <p class="cnx-carte__intro">Toutes les sessions ouvertes de ce compte seront fermées. Vous vous connecterez ensuite avec ce mot de passe, puis le code de votre application d'authentification.</p>
  <form class="cnx-form" method="post" action="<?= e(A::url('nouveau-mot-de-passe/')) ?>" novalidate>
    <?= Csrf::field() ?><input type="hidden" name="jeton" value="<?= e($token) ?>">
    <?php if ($errors): ?><p class="adm-alerte adm-alerte--erreur" role="alert">Mot de passe inchangé : corrigez le champ signalé.</p><?php endif ?>
    <?php partial('password', ['id' => 'r-new', 'name' => 'password', 'label' => 'Nouveau mot de passe', 'autocomplete' => 'new-password', 'hint' => 'Au moins ' . AdminAuth::MIN_PASSWORD . ' caractères.', 'error' => $errors['password'] ?? null]) ?>
    <?php partial('password', ['id' => 'r-confirm', 'name' => 'confirm', 'label' => 'Confirmer le nouveau mot de passe', 'autocomplete' => 'new-password', 'error' => $errors['confirm'] ?? null]) ?>
    <button class="btn btn--plein" type="submit">Enregistrer le mot de passe</button>
  </form>
<?php endif ?>
