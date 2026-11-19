<?php
/** @var array<string, mixed> $user @var list<array{href: string, label: string, n: int}> $stats @var list<array<string, mixed>> $actions */
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

<?php if ($stats !== []): ?>
<ul class="adm-chiffres" role="list">
  <?php foreach ($stats as $s): ?>
  <li><a class="adm-chiffre" href="<?= e($s['href']) ?>"><strong class="adm-chiffre__n"><?= (int) $s['n'] ?></strong><span><?= e($s['label']) ?></span></a></li>
  <?php endforeach ?>
</ul>
<?php endif ?>
