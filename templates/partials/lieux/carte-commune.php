<?php
/**
 * Petite carte d'une commune (fiche d'un événement) : la commune lue dans l'adresse saisie (nom le plus long trouvé, sans
 * accents ni tirets), dessinée en couleur au milieu de ses voisines, d'après config/carte-communes.json. Rien n'est
 * affiché si aucune commune connue n'apparaît dans l'adresse : jamais de lieu deviné.
 * @var string $adresse @var string $titre nom accessible (« {commune} sur la carte »)
 */
$fichier = ROOT . '/config/carte-communes.json';
$carte = is_file($fichier) ? json_decode((string) file_get_contents($fichier), true) : null;
if (!is_array($carte) || empty($carte['communes']) || trim($adresse) === '') {
    return;
}
$simple = static fn (string $t): string => ' ' . trim((string) preg_replace('/[^a-z0-9]+/', ' ', strtolower(strtr($t, ['é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e', 'É' => 'e', 'à' => 'a', 'â' => 'a', 'î' => 'i', 'ï' => 'i', 'ô' => 'o', 'û' => 'u', 'ù' => 'u', 'ç' => 'c'])))) . ' ';
$texte = $simple($adresse);
$trouvee = null;
foreach ($carte['communes'] as $c) {
    $nom = $simple((string) $c['nom']);
    if (str_contains($texte, $nom) && ($trouvee === null || strlen($nom) > strlen($simple((string) $trouvee['nom'])))) {
        $trouvee = $c;
    }
}
if ($trouvee === null) {
    return;
}
// Cadre : la commune et ses environs (contour lu dans son tracé), au moins 140 unités de côté.
// Tracé en « M x y l dx dy … z » (coordonnées relatives, scripts/carte-communes.mjs) : points absolus recalculés.
$xs = [];
$ys = [];
preg_match_all('/[Mlz]|-?(?:\d+\.?\d*|\.\d+)/', (string) $trouvee['d'], $m);
$commande = 'M';
$nombres = [];
$x = $y = 0.0;
foreach ($m[0] as $jeton) {
    if (in_array($jeton, ['M', 'l', 'z'], true)) {
        $commande = $jeton;
        $nombres = [];
        continue;
    }
    $nombres[] = (float) $jeton;
    if (count($nombres) === 2) {
        [$x, $y] = $commande === 'M' ? $nombres : [$x + $nombres[0], $y + $nombres[1]];
        $xs[] = $x;
        $ys[] = $y;
        $nombres = [];
        if ($commande === 'M') {
            $commande = 'l';
        }
    }
}
if ($xs === []) {
    return;
}
$cx = (min($xs) + max($xs)) / 2;
$cy = (min($ys) + max($ys)) / 2;
$cote = max(140, max(max($xs) - min($xs), max($ys) - min($ys)) * 2.2);
$vue = implode(' ', array_map(static fn (float $v): string => (string) round($v, 1), [$cx - $cote * 0.65, $cy - $cote / 2, $cote * 1.3, $cote]));
// Repères : les quatre communes voisines les plus proches (centre dans le cadre), en petit.
[$vx, $vy, $vw, $vh] = array_map('floatval', explode(' ', $vue));
$voisines = array_filter($carte['communes'], static fn ($c) => $c['code'] !== $trouvee['code'] && $c['cx'] > $vx + 15 && $c['cx'] < $vx + $vw - 15 && $c['cy'] > $vy + 8 && $c['cy'] < $vy + $vh - 8);
usort($voisines, static fn ($a, $b) => (($a['cx'] - $cx) ** 2 + ($a['cy'] - $cy) ** 2) <=> (($b['cx'] - $cx) ** 2 + ($b['cy'] - $cy) ** 2));
$voisines = array_slice($voisines, 0, 4);
$titre = str_replace('{commune}', (string) $trouvee['nom'], $titre);
?>
<svg class="carte-commune" viewBox="<?= e($vue) ?>" role="img" aria-labelledby="carte-commune-titre" focusable="false">
  <title id="carte-commune-titre"><?= e($titre) ?></title>
  <rect class="carte-lieux__mer" x="-2000" y="-2000" width="5000" height="5000"/>
  <?php foreach ($carte['communes'] as $c): ?><path class="carte-lieux__commune<?= $c['code'] === $trouvee['code'] ? ' carte-commune__choisie' : '' ?>" d="<?= e((string) $c['d']) ?>"/><?php endforeach ?>
  <?php foreach ($voisines as $c): ?><text class="carte-commune__voisine" x="<?= e((string) $c['cx']) ?>" y="<?= e((string) $c['cy']) ?>"><?= e((string) $c['nom']) ?></text><?php endforeach ?>
  <text class="carte-commune__nom" x="<?= e((string) round($cx, 1)) ?>" y="<?= e((string) round($cy, 1)) ?>"><?= e((string) $trouvee['nom']) ?></text>
</svg>
