<?php
/**
 * Flèches Monter / Descendre d'une fiche dans son groupe (sans JavaScript : deux petits formulaires).
 * @var string $key @var array<string, mixed> $row @var bool $isFirst @var bool $isLast @var string $moveTab onglet à rouvrir @var string $rowName
 */
use App\Admin\Ui;
use App\Core\Csrf;
?>
<div class="adm-deplacer" role="group" aria-label="Place de « <?= e($rowName) ?> »">
  <?php foreach (['haut' => ['up', 'Monter', $isFirst], 'bas' => ['down', 'Descendre', $isLast]] as $sens => [$icon, $label, $off]): ?>
  <form method="post" action="/admin/<?= e($key) ?>/<?= (int) $row['id'] ?>/deplacer">
    <?= Csrf::field() ?><input type="hidden" name="sens" value="<?= $sens ?>"><input type="hidden" name="onglet" value="<?= e($moveTab) ?>">
    <button class="btn btn--discret btn--icone" type="submit"<?= $off ? ' disabled' : '' ?>><?= Ui::icon($icon) ?><span class="adm-masque"><?= $label ?> « <?= e($rowName) ?> »</span></button>
  </form>
  <?php endforeach ?>
</div>
