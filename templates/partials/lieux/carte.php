<?php
/**
 * Carte des lieux dessinée par Kouzia (décisions du 2026-11-17, à valider par Géraldine Gabillet) : contours des communes
 * (config/carte-communes.json, généré par scripts/carte-communes.mjs) et un point par lieu, placé d'après ses coordonnées
 * avec la même projection. Vue de départ cadrée sur les lieux ; zoom, déplacement et regroupement des points proches
 * par lieux.js (souris, doigt et clavier). Une commune qui a des lieux est un lien vers son groupe dans la liste.
 * Aucun service extérieur, aucune image.
 * @var list<array> $groupes groupes de Groupes::parCommune (quatre calques) @var array $t textes de la page
 * @var bool $details routes moyennes en plus (carte en grand)
 */
use App\Lieux\Groupes;

$fichier = ROOT . '/config/carte-communes.json';
$carte = is_file($fichier) ? json_decode((string) file_get_contents($fichier), true) : null;
if (!is_array($carte) || empty($carte['communes'])) {
    return;
}
$p = $carte['projection'];
$point = static fn (float $lat, float $lon): array => [round(($lon - $p['lonMin']) * $p['cos'] * $p['k'], 1), round(($p['latMax'] - $lat) * $p['k'], 1)];
$nombres = [];
$xs = [];
$ys = [];
foreach ($groupes as $g) {
    $nombres[$g['cle']] = count($g['lieux']);
    foreach ($g['lieux'] as $l) {
        if ($l['latitude'] !== null && $l['longitude'] !== null) {
            [$x, $y] = $point((float) $l['latitude'], (float) $l['longitude']);
            $xs[] = $x;
            $ys[] = $y;
        }
    }
}
// Vue de départ : les lieux et une marge, dans les limites du dessin.
$largeur = (float) $carte['largeur'];
$hauteur = (float) $carte['hauteur'];
$marge = 35;
$vx = $xs !== [] ? max(0, min($xs) - $marge) : 0;
$vy = $ys !== [] ? max(0, min($ys) - $marge) : 0;
$vw = $xs !== [] ? min($largeur, max($xs) + $marge) - $vx : $largeur;
$vh = $ys !== [] ? min($hauteur, max($ys) + $marge) - $vy : $hauteur;
$vue = implode(' ', array_map(static fn (float $v): string => (string) round($v, 1), [$vx, $vy, $vw, $vh]));
?>
<svg class="carte-lieux" viewBox="<?= e($vue) ?>" data-vue="<?= e($vue) ?>" data-limites="0 0 <?= (int) $largeur ?> <?= (int) $hauteur ?>" role="group" aria-labelledby="carte-lieux-titre" focusable="false">
  <title id="carte-lieux-titre"><?= e($t['carte_svg_titre']) ?></title>
  <rect class="carte-lieux__mer" x="-<?= (int) $largeur ?>" y="-<?= (int) $hauteur ?>" width="<?= (int) $largeur * 3 ?>" height="<?= (int) $hauteur * 3 ?>"/>
  <?php foreach ($carte['communes'] as $c): $cle = Groupes::ancrePour((string) $c['nom']); $n = $nombres[$cle] ?? 0; ?>
  <?php if ($n > 0): ?>
  <a href="#<?= e($cle) ?>" class="carte-lieux__lien" data-commune="<?= e($cle) ?>" data-nom="<?= e((string) $c['nom']) ?>" data-cx="<?= e((string) $c['cx']) ?>" data-cy="<?= e((string) $c['cy']) ?>"><title><?= e($c['nom'] . ' : ' . $n . ' ' . ($n > 1 ? $t['lieux_pluriel'] : $t['lieu_singulier'])) ?></title><path class="carte-lieux__commune carte-lieux__commune--lieux" d="<?= e((string) $c['d']) ?>"/></a>
  <?php else: ?>
  <path class="carte-lieux__commune" d="<?= e((string) $c['d']) ?>"/>
  <?php endif ?>
  <?php endforeach ?>
  <?php // Contours de la commune survolée et de la commune choisie, redessinés par-dessus leurs voisines (lieux.js). ?>
  <path class="carte-lieux__contour carte-lieux__contour--choisi" d="" aria-hidden="true" data-contour-choisi/>
  <path class="carte-lieux__contour" d="" aria-hidden="true" data-contour/>
  <?php $f = ROOT . '/config/carte-routes.json'; $ign = is_file($f) ? json_decode((string) file_get_contents($f), true) : null; if (is_array($ign)): // IGN, BD TOPO : grandes routes et cours d'eau (routes moyennes sur la carte en grand) ?>
  <g class="carte-lieux__reseau" aria-hidden="true">
    <path class="carte-lieux__eau" d="<?= e((string) $ign['eau']) ?>"/>
    <?php if (!empty($details)): ?><path class="carte-lieux__route carte-lieux__route--moyenne" d="<?= e((string) $ign['routes']['moyennes']) ?>"/><?php endif ?>
    <path class="carte-lieux__route carte-lieux__route--grande" d="<?= e((string) $ign['routes']['grandes']) ?>"/>
  </g>
  <?php endif ?>
  <g class="carte-lieux__points" aria-hidden="true">
    <?php foreach ($groupes as $g): foreach ($g['lieux'] as $l): if ($l['latitude'] === null || $l['longitude'] === null) continue; [$x, $y] = $point((float) $l['latitude'], (float) $l['longitude']); ?><circle cx="<?= $x ?>" cy="<?= $y ?>" r="7" class="carte-lieux__point--<?= e((string) $l['statut']) ?>" data-calque="<?= e((string) $l['statut']) ?>" data-groupe-point="<?= e($g['cle']) ?>" data-lieu-id="<?= (int) $l['id'] ?>"/><?php endforeach; endforeach ?>
  </g>
  <g class="carte-lieux__grappes" data-grappes></g>
  <g class="carte-lieux__noms" aria-hidden="true">
    <?php foreach ($carte['communes'] as $c): $n = $nombres[Groupes::ancrePour((string) $c['nom'])] ?? 0; if ($n < 5) continue; ?><text x="<?= e((string) $c['cx']) ?>" y="<?= e((string) $c['cy']) ?>"><?= e((string) $c['nom']) ?></text><?php endforeach ?>
  </g>
  <g class="carte-lieux__noms carte-lieux__noms--choisi" aria-hidden="true"><text data-nom-choisi></text></g>
</svg>
