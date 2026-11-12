<?php
// Document commun à toutes les familles de gabarits (Contenu, Isolee) et à l'accueil.
// Props : titre, description, [noindex], [image] (chemin d'une image 1200×630),
// [parents] (liste de [titre, chemin] entre l'accueil et la page, pour le fil d'Ariane).
// En-tête, fil d'Ariane et pied de page sont des composants (src/components) : un bloc, un fichier.
$site = data('site');
$chemin = chemin_courant();
$titreComplet = $props['titre'] . ' · ' . $site['nom'];
$image = $props['image'] ?? $site['image'] ?? null;
// Fil d'Ariane : une seule liste pour le HTML visible et le JSON-LD (rule web-seo).
$ariane = $chemin === '/' || !empty($props['noindex'])
    ? []
    : array_merge([['Accueil', '/']], $props['parents'] ?? [], [[$props['titre'], $chemin]]);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($titreComplet) ?></title>
  <meta name="description" content="<?= e($props['description']) ?>">
<?php if (!empty($props['noindex'])): ?>
  <meta name="robots" content="noindex">
<?php else: ?>
  <link rel="canonical" href="<?= e(url_absolue($chemin)) ?>">
  <meta property="og:type" content="website">
  <meta property="og:locale" content="fr_FR">
  <meta property="og:site_name" content="<?= e($site['nom']) ?>">
  <meta property="og:title" content="<?= e($titreComplet) ?>">
  <meta property="og:description" content="<?= e($props['description']) ?>">
  <meta property="og:url" content="<?= e(url_absolue($chemin)) ?>">
<?php if ($image): ?>
  <meta property="og:image" content="<?= e(url_absolue($image)) ?>">
  <meta property="og:image:width" content="1200">
  <meta property="og:image:height" content="630">
  <meta name="twitter:card" content="summary_large_image">
<?php endif ?>
<?php endif ?>
<?php if (is_file(ROOT . '/public/favicon.svg')): ?>
  <link rel="icon" href="/favicon.svg" type="image/svg+xml">
<?php endif ?>
<?php if (is_file(ROOT . '/public/apple-touch-icon.png')): ?>
  <link rel="apple-touch-icon" href="/apple-touch-icon.png">
<?php endif ?>
  <link rel="stylesheet" href="<?= e(css_href()) ?>">
<?php if ($ariane): ?>
  <script type="application/ld+json"><?= json_encode([
      '@context' => 'https://schema.org',
      '@type' => 'BreadcrumbList',
      '@id' => url_absolue($chemin) . '#ariane',
      'itemListElement' => array_map(fn($i, $p) => [
          '@type' => 'ListItem', 'position' => $i + 1, 'name' => $p[0], 'item' => url_absolue($p[1]),
      ], array_keys($ariane), $ariane),
  ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?></script>
<?php endif ?>
</head>
<body>
  <a class="evitement" href="#contenu">Aller au contenu</a>
<?php component('en-tete'); ?>
  <main id="contenu">
<?php if ($ariane): component('fil-ariane', ['liens' => $ariane]); endif ?>
<?php $contenu(); ?>
  </main>
<?php component('pied-de-page'); ?>
</body>
</html>
