<?php
// Pied de page : contact de site.json, liens de navigation.json (pied : légal, accessibilité, plan du site) et crédit
// du prestataire (.claude/kouzia.json, une seule source ; complément du projet : creditComplement de site.json).
$site = data('site');
$liens = data('navigation')['pied'];
$fichierCredit = dirname(__DIR__, 2) . '/.claude/kouzia.json';
$credit = is_file($fichierCredit) ? json_decode((string) file_get_contents($fichierCredit), true, 512, JSON_THROW_ON_ERROR)['credit'] : null;
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
<?php if ($credit): ?>
    <p class="credit"><?= e($credit['avant']) ?><a href="<?= e($credit['lien']['url']) ?>"><?= e($credit['lien']['texte']) ?></a><?= e($credit['apres']) ?><?php if (!empty($site['creditComplement'])): ?> <?= e($site['creditComplement']) ?><?php endif ?><?= e($credit['fin']) ?></p>
<?php endif ?>
  </footer>
