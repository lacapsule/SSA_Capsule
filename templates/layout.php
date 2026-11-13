<?php
/**
 * Gabarit de page public (contrat des modules, NOTES.md).
 * @var string $content @var string $title
 * Facultatif : string $description, bool $noindex, list<string> $styles et $scripts (feuilles et scripts du module, chemins de public/).
 * Un script à charger en module s'écrit ['src' => '/assets/js/x.js', 'module' => true] (type="module", différé d'office) ; les autres en defer.
 */
$styles = isset($styles) && is_array($styles) ? $styles : [];
$scripts = isset($scripts) && is_array($scripts) ? $scripts : [];
?>
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title) ?></title>
<?php if (isset($description) && is_string($description) && $description !== ''): ?>
<meta name="description" content="<?= e($description) ?>">
<?php endif ?>
<?php if (!empty($noindex)): ?>
<meta name="robots" content="noindex">
<?php endif ?>
<link rel="stylesheet" href="<?= e(asset('/assets/css/site.css')) ?>">
<?php foreach ($styles as $href): ?>
<link rel="stylesheet" href="<?= e(asset((string) $href)) ?>">
<?php endforeach ?>
<script src="<?= e(asset('/assets/js/site.js')) ?>" defer></script>
<?php foreach ($scripts as $src): $module = is_array($src) && !empty($src['module']); $src = is_array($src) ? (string) $src['src'] : (string) $src; ?>
<script src="<?= e(asset($src)) ?>"<?= $module ? ' type="module"' : ' defer' ?>></script>
<?php endforeach ?>
</head>
<body>
<a class="lien-evitement" href="#contenu">Aller au contenu</a>
<main id="contenu" tabindex="-1">
<?= $content ?>
</main>
</body>
</html>
