<?php
// Pied de page : contact de site.json et liens de navigation.json (pied : légal, accessibilité, plan du site).
$site = data('site');
$liens = data('navigation')['pied'];
?>
  <footer class="pied-de-page">
    <p><a href="mailto:<?= e($site['contact']['email']) ?>"><?= e($site['contact']['email']) ?></a><?php if (!empty($site['contact']['telephoneAffiche'])): ?> · <a href="tel:<?= e(preg_replace('/[^0-9+]/', '', $site['contact']['telephoneAffiche'])) ?>"><?= e($site['contact']['telephoneAffiche']) ?></a><?php endif ?></p>
<?php if ($liens): ?>
    <nav aria-label="Liens du pied de page">
      <ul class="navigation">
<?php foreach ($liens as $lien): ?>
        <li><a href="<?= e($lien['chemin']) ?>"><?= e($lien['titre']) ?></a></li>
<?php endforeach ?>
      </ul>
    </nav>
<?php endif ?>
  </footer>
