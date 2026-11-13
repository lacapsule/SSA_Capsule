<?php
/**
 * Haut de page intérieure : fil d'Ariane visible et BreadcrumbList (rule web-seo), titre h1, chapeau.
 * @var string $chemin chemin interne @var string $titre @var string $chapo
 * Facultatif : array{chemin: string, libelle: string}|null $parent (rubrique d'une fiche) ;
 * string $photo (photo du site actuel en fond, sous un voile, chargée en priorité : élément LCP).
 */
use App\Langue\Langue;

$parent = isset($parent) && is_array($parent) ? $parent : null;
$niveaux = [['chemin' => '/', 'libelle' => t('menu.accueil')]];
if ($parent !== null) {
    $niveaux[] = $parent;
}
$niveaux[] = ['chemin' => $chemin, 'libelle' => $titre];
$liste = [];
foreach ($niveaux as $i => $n) {
    $liste[] = ['@type' => 'ListItem', 'position' => $i + 1, 'name' => $n['libelle'], 'item' => Langue::absolue($n['chemin'])];
}
?>
<?php $photo = isset($photo) && is_string($photo) ? $photo : ''; ?>
<div class="page-head<?= $photo !== '' ? ' page-head--photo' : '' ?>">
  <?php if ($photo !== ''): ?><img class="page-head__photo" src="<?= e($photo) ?>" alt="" width="1920" height="1281" fetchpriority="high" decoding="async"><?php endif ?>
  <div class="page-head__inner container">
    <nav class="breadcrumb" aria-label="<?= e(t('site.fil_ariane')) ?>">
      <ol>
        <?php foreach ($niveaux as $i => $n): ?>
        <?php if ($i === count($niveaux) - 1): ?>
        <li aria-current="page"><?= e($n['libelle']) ?></li>
        <?php else: ?>
        <li><a href="<?= e(lurl($n['chemin'])) ?>"><?= e($n['libelle']) ?></a></li>
        <?php endif ?>
        <?php endforeach ?>
      </ol>
    </nav>
    <h1><?= e($titre) ?></h1>
    <?php if (($chapo ?? '') !== ''): ?><p class="page-head__intro"><?= e($chapo) ?></p><?php endif ?>
  </div>
</div>
<script type="application/ld+json"><?= json_encode(['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => $liste], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) ?></script>
