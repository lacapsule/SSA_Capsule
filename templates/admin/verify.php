<?php
/** Étape 2 : code de l'application, ou code de secours. @var string|null $error @var bool $backup */
use App\Admin\AdminController as A;
use App\Core\Csrf;

$issuer = (string) (config('admin.issuer') ?: config('app.name', 'Back-office'));
?>
<p class="cnx-carte__etape">Étape 2 sur 2</p>
<h1 class="cnx-carte__titre"><?= $backup ? 'Code de secours' : 'Code de vérification' ?></h1>
<p class="cnx-carte__intro"><?= $backup
    ? 'Saisissez l\'un de vos codes de secours. Chaque code ne sert qu\'une fois.'
    : 'Ouvrez votre application d\'authentification et saisissez le code à 6 chiffres affiché pour « ' . e($issuer) . ' ».' ?></p>
<form class="cnx-form" method="post" action="<?= e(A::url('verification/')) ?>" novalidate>
  <?= Csrf::field() ?>
  <?php if ($backup): ?><input type="hidden" name="backup" value="1"><?php endif ?>
  <div class="adm-champ">
    <label for="a-code"><?= $backup ? 'Code de secours' : 'Code à 6 chiffres' ?></label>
    <?php if ($backup): ?><p class="adm-champ__aide" id="code-aide">10 caractères, tiret facultatif, par exemple ABCDE-23456.</p><?php endif ?>
    <?php if ($error): ?><p class="adm-alerte adm-alerte--erreur" role="alert" id="code-erreur"><?= e($error) ?></p><?php endif ?>
    <?php if ($backup): ?>
      <input id="a-code" name="code" class="adm-saisie auth-code" autocomplete="off" autocapitalize="characters" spellcheck="false" aria-describedby="code-aide<?= $error ? ' code-erreur' : '' ?>" required autofocus<?= $error ? ' aria-invalid="true"' : '' ?>>
    <?php else: ?>
      <input id="a-code" name="code" class="adm-saisie auth-code" inputmode="numeric" pattern="[0-9]*" autocomplete="one-time-code" maxlength="6" data-chiffres required autofocus<?= $error ? ' aria-invalid="true" aria-describedby="code-erreur"' : '' ?>>
    <?php endif ?>
  </div>
  <button class="btn btn--plein" type="submit">Vérifier et entrer</button>
</form>
<p class="cnx-carte__bas">
  <?php if ($backup): ?><a class="adm-lien cnx-lien" href="<?= e(A::url('verification/')) ?>">Utiliser l'application d'authentification</a>
  <?php else: ?><a class="adm-lien cnx-lien" href="<?= e(A::url('verification/')) ?>?secours">Téléphone perdu ? Utiliser un code de secours</a><?php endif ?>
</p>
