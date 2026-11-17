<?php
/** Financeurs sur cartouches blancs, chaque logo mène au site du financeur ; table des partenaires (administration). */
use App\Core\Db;

$financeurs = Db::run("SELECT nom, url, logo FROM partenaires WHERE groupe = 'financeur' ORDER BY position")->fetchAll();
if ($financeurs === []) {
    return;
}
?>
<div class="qui__financeurs">
  <h3 class="sur-titre"><?= e(t('partenaires.financeurs')) ?></h3>
  <ul>
    <?php foreach ($financeurs as $l): $fichier = ROOT . '/public' . $l['logo']; $taille = is_file($fichier) ? getimagesize($fichier) : false; $w = $taille ? (int) round(80 * $taille[0] / max(1, $taille[1])) : 80; $img = '<img src="' . e((string) $l['logo']) . '" alt="' . e((string) $l['nom']) . '" width="' . $w . '" height="80" loading="lazy" decoding="async">'; ?>
    <li><?php if (preg_match('#^https?://#i', (string) $l['url']) === 1): ?><a href="<?= e((string) $l['url']) ?>" rel="noopener"><?= $img ?></a><?php else: ?><?= $img ?><?php endif ?></li>
    <?php endforeach ?>
  </ul>
</div>
