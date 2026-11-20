<?php
/**
 * @var array<string, mixed> $user @var list<array{href: string, label: string, n: int}> $stats @var list<array<string, mixed>> $actions
 * @var list<array{href: string, label: string, action: string}> $aFaire travail en attente (config/tableau-de-bord.php)
 */
use App\Admin\Ui;

$first = explode(' ', (string) ($user['name'] ?: $user['email']))[0];
?>
<header class="adm-entete-page">
  <div class="adm-entete-page__textes">
    <h1 class="adm-entete-page__titre">Bonjour <?= e($first) ?></h1>
    <p class="adm-entete-page__sous-titre">Choisissez ce que vous voulez modifier. Chaque changement enregistré est en ligne aussitôt.</p>
  </div>
  <?php if ($actions !== []): ?>
  <div class="adm-entete-page__actions">
    <?php foreach ($actions as $a): ?>
    <a class="btn<?= empty($a['principal']) ? ' btn--neutre' : '' ?>" href="<?= e((string) $a['href']) ?>"><?= Ui::icon((string) ($a['icon'] ?? 'arrow')) ?><?= e((string) $a['label']) ?></a>
    <?php endforeach ?>
  </div>
  <?php endif ?>
</header>

<?php if (!empty($aFaire)): ?>
<section class="adm-carte" aria-labelledby="a-faire-titre">
  <div class="adm-carte__entete"><h2 class="adm-carte__titre" id="a-faire-titre">À faire</h2></div>
  <ul class="adm-lignes" role="list">
    <?php foreach ($aFaire as $t): ?>
    <li><span class="adm-lignes__principal adm-lignes__titre"><?= e($t['label']) ?></span><a class="btn btn--neutre" href="<?= e($t['href']) ?>"><?= e($t['action']) ?><?= Ui::icon('arrow') ?></a></li>
    <?php endforeach ?>
  </ul>
</section>
<?php endif ?>

<?php if ($stats !== []): ?>
<ul class="adm-chiffres" role="list">
  <?php foreach ($stats as $s): ?>
  <li><a class="adm-chiffre" href="<?= e($s['href']) ?>"><strong class="adm-chiffre__n"><?= (int) $s['n'] ?></strong><span><?= e($s['label']) ?></span></a></li>
  <?php endforeach ?>
</ul>
<?php endif ?>
