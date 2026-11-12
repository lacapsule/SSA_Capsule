<?php
// En-tête : nom du site et menu principal (src/data/navigation.json), page courante marquée.
$site = data('site');
$liens = data('navigation')['principale'];
$courant = chemin_courant();
?>
  <header class="en-tete">
    <a href="/"><?= e($site['nom']) ?></a>
<?php if ($liens): ?>
    <nav aria-label="Menu principal">
      <ul class="navigation">
<?php foreach ($liens as $lien): ?>
        <li><a href="<?= e($lien['chemin']) ?>"<?= str_starts_with($courant, $lien['chemin']) && ($lien['chemin'] !== '/' || $courant === '/') ? ' aria-current="page"' : '' ?>><?= e($lien['titre']) ?></a></li>
<?php endforeach ?>
      </ul>
    </nav>
<?php endif ?>
  </header>
