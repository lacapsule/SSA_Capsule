<?php
/** Logos des partenaires et financeurs en grille fixe, liens vers leurs sites. @var string $titre_id */
use App\Core\Db;

$groupes = [];
foreach (Db::run('SELECT nom, url, logo, groupe, hauteur_rem FROM partenaires ORDER BY groupe DESC, position')->fetchAll() as $l) {
    $groupes[$l['groupe']][] = $l;
}
if ($groupes === []) {
    return;
}
?>
<section class="section section--logos" aria-labelledby="<?= e($titre_id) ?>">
  <div class="container">
    <div class="section__head"><h2 id="<?= e($titre_id) ?>"><?= e(t('partenaires.titre')) ?></h2></div>
    <?php foreach (['partenaire' => 'partenaires.accompagnent', 'financeur' => 'partenaires.financeurs'] as $groupe => $cle): if (empty($groupes[$groupe])) continue; ?>
    <h3 class="logos-titre"><?= e(t($cle)) ?></h3>
    <ul class="grille-logos">
      <?php foreach ($groupes[$groupe] as $l): ?>
      <?php
      // Même surface visuelle pour chaque logo (hauteur de logos-recadres.mjs), largeur tirée du fichier.
      $taille = is_file(ROOT . '/public' . $l['logo']) ? getimagesize(ROOT . '/public' . $l['logo']) : false;
      $h = (int) round(16 * (float) ($l['hauteur_rem'] ?: 3));
      $w = $taille ? (int) round($h * $taille[0] / max(1, $taille[1])) : $h;
      ?>
      <li><a class="grille-logos__lien" href="<?= e((string) $l['url']) ?>" rel="noopener"><img src="<?= e((string) $l['logo']) ?>" alt="<?= e((string) $l['nom']) ?>" width="<?= $w ?>" height="<?= $h ?>" loading="lazy" decoding="async"></a></li>
      <?php endforeach ?>
    </ul>
    <?php endforeach ?>
  </div>
</section>
