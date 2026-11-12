<?php
// Fil d'Ariane visible ; sa liste ($props['liens'], calculée par Base) alimente aussi le BreadcrumbList.
$liens = $props['liens'];
?>
    <nav aria-label="Fil d'Ariane">
      <ol class="ariane">
<?php foreach ($liens as $i => [$nom, $lien]): ?>
        <li><?php if ($i === array_key_last($liens)): ?><span aria-current="page"><?= e($nom) ?></span><?php else: ?><a href="<?= e($lien) ?>"><?= e($nom) ?></a><?php endif ?></li>
<?php endforeach ?>
      </ol>
    </nav>
