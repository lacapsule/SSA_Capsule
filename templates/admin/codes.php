<?php
/** Codes de secours, montrés une seule fois. @var list<string> $codes */
use App\Admin\AdminController as A;
?>
<h1 class="cnx-carte__titre">Vos codes de secours</h1>
<p class="cnx-carte__intro">Gardez-les en lieu sûr (sur papier ou dans un gestionnaire de mots de passe). Ils permettent d'entrer si vous perdez votre téléphone. Chaque code ne sert qu'une fois ; ils ne seront plus affichés.</p>
<ul class="auth-codes" aria-label="Codes de secours">
  <?php foreach ($codes as $c): ?><li><?= e($c) ?></li><?php endforeach ?>
</ul>
<a class="btn btn--plein" href="<?= e($suite ?? A::url()) ?>">J'ai noté mes codes, entrer</a>
