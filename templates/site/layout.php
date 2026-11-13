<?php
/**
 * Mise en page du site public (gabarits/site-public-php, adaptée au projet et à modules/multilingue) :
 * lien d'évitement, en-tête (logo, menu, langue), contenu, pied de page (coordonnées, liens légaux, langue).
 * @var array<string, string> $meta title, description, h1 @var string $path chemin interne @var string $content
 * @var bool $noindex @var list<string>|null $disponibles langues où la page existe (null : toutes)
 * Contrat des modules du socle (config('app.layout') = 'site/layout') : $title, $description, $styles et $scripts
 * sont acceptés aussi ; $meta et $path sont alors déduits.
 */
use App\Langue\Langue;
use App\Site\Site;

$meta = isset($meta) && is_array($meta) ? $meta : ['title' => (string) ($title ?? ''), 'description' => (string) ($description ?? ''), 'h1' => (string) ($title ?? '')];
$path = isset($path) && is_string($path) ? $path : Langue::chemin();
$styles = isset($styles) && is_array($styles) ? $styles : [];
$scripts = isset($scripts) && is_array($scripts) ? $scripts : [];
$disponibles = isset($disponibles) && is_array($disponibles) ? $disponibles : null;
$noindex = !empty($noindex);
$name = (string) Site::get('nom');
$short = (string) Site::get('nom_court', $name);
$title = ($meta['title'] ?? '') !== '' ? $meta['title'] : ($meta['h1'] ?? $name);
// Suffixe de marque seulement s'il tient sous 60 caractères et que ce n'est pas déjà le nom.
if ($path !== '/' && $title !== $name && (int) preg_match_all('/./su', $title . ' · ' . $short) < 60) {
    $title .= ' · ' . $short;
}
$canonical = Langue::absolue($path);
$menu = (array) Site::get('menu', []);
$legal = (array) Site::get('legal', []);
$adresse = (array) Site::get('adresse', []);
$tel = (string) Site::get('telephone');
$email = (string) Site::get('email');
?><!doctype html>
<html lang="<?= e(Langue::hreflang()) ?>" dir="<?= e(Langue::dir()) ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title) ?></title>
<?php if (($meta['description'] ?? '') !== ''): ?>
<meta name="description" content="<?= e($meta['description']) ?>">
<?php endif ?>
<?php partial('langue/head', ['chemin' => $path, 'disponibles' => $disponibles, 'noindex' => $noindex]) ?>
<?php if (!$noindex): ?>
<meta property="og:type" content="website">
<meta property="og:site_name" content="<?= e($name) ?>">
<meta property="og:title" content="<?= e($title) ?>">
<meta property="og:description" content="<?= e($meta['description'] ?? '') ?>">
<meta property="og:url" content="<?= e($canonical) ?>">
<?php endif ?>
<link rel="stylesheet" href="<?= e(Site::asset('/assets/css/tokens.css')) ?>">
<link rel="stylesheet" href="<?= e(Site::asset('/assets/css/charte.css')) ?>">
<link rel="stylesheet" href="<?= e(Site::asset('/assets/css/site.css')) ?>">
<link rel="stylesheet" href="<?= e(Site::asset('/assets/css/langue.css')) ?>">
<link rel="stylesheet" href="<?= e(Site::asset('/assets/css/navigation-fiches.css')) ?>">
<link rel="stylesheet" href="<?= e(Site::asset('/assets/css/ssa.css')) ?>">
<?php foreach ($styles as $href): ?>
<link rel="stylesheet" href="<?= e(Site::asset((string) $href)) ?>">
<?php endforeach ?>
<script src="<?= e(Site::asset('/assets/js/site.js')) ?>" defer></script>
<?php foreach ($scripts as $src): $module = is_array($src) && !empty($src['module']); $src = is_array($src) ? (string) $src['src'] : (string) $src; ?>
<script src="<?= e(Site::asset($src)) ?>"<?= $module ? ' type="module"' : ' defer' ?>></script>
<?php endforeach ?>
<?php if ($path === '/' && !$noindex): ?>
<script type="application/ld+json"><?= json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'Organization',
    '@id' => Site::url() . '/#organisation',
    'name' => $name,
    'alternateName' => $short,
    'url' => Site::url() . '/',
    'email' => $email,
    'telephone' => $tel,
    'address' => [
        '@type' => 'PostalAddress',
        'streetAddress' => (string) ($adresse['rue'] ?? ''),
        'postalCode' => (string) ($adresse['code_postal'] ?? ''),
        'addressLocality' => (string) ($adresse['ville'] ?? ''),
        'addressCountry' => 'FR',
    ],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) ?></script>
<?php endif ?>
</head>
<body id="haut">
<a class="skip-link" href="#contenu"><?= e(t('site.aller_au_contenu')) ?></a>
<header class="site-header">
  <div class="site-header__inner container">
    <a class="site-header__logo" href="<?= e(lurl('/')) ?>"<?= $path === '/' ? ' aria-current="page"' : '' ?>><img src="/assets/img/logo.svg" alt="" width="433" height="446"><span><?= e($short) ?></span><span class="visually-hidden">, <?= e(t('site.accueil')) ?></span></a>
    <nav class="site-nav" aria-label="<?= e(t('menu.principal')) ?>" data-nav>
      <button class="site-nav__toggle" type="button" aria-expanded="false" aria-controls="menu-principal" hidden><span class="site-nav__bars" aria-hidden="true"></span><?= e(t('menu.ouvrir')) ?></button>
      <ul class="site-nav__list" id="menu-principal">
        <?php foreach ($menu as $href => $cle): $actif = $href === '/' ? $path === '/' : str_starts_with($path, (string) $href); ?>
        <li><a href="<?= e(lurl((string) $href)) ?>"<?= $actif ? ' aria-current="page"' : '' ?>><?= e(t((string) $cle)) ?></a></li>
        <?php endforeach ?>
      </ul>
    </nav>
    <?php partial('langue/selecteur', ['chemin' => $path, 'disponibles' => $disponibles, 'variante' => 'liste', 'id' => 'langues-entete']) ?>
  </div>
</header>

<main id="contenu" tabindex="-1">
<?= $content ?>
</main>

<footer class="site-footer">
  <div class="container">
    <div class="site-footer__top">
      <div>
        <h2 class="site-footer__title"><?= e(t('pied.coordonnees')) ?></h2>
        <p><?= e(t('pied.contact_structure', ['structure' => (string) Site::get('contact_structure')])) ?></p>
        <address>
          <?= e((string) ($adresse['lieu'] ?? '')) ?><br>
          <?= e((string) ($adresse['rue'] ?? '')) ?><br>
          <?= e(($adresse['code_postal'] ?? '') . ' ' . ($adresse['ville'] ?? '')) ?><br>
          <?php if ($tel !== ''): ?><?= e(t('pied.telephone')) ?> : <a href="tel:+33<?= e(substr(preg_replace('/\D/', '', $tel), 1)) ?>"><?= e($tel) ?></a><br><?php endif ?>
          <?php if ($email !== ''): ?><?= e(t('pied.email')) ?> : <a href="mailto:<?= e($email) ?>"><?= e($email) ?></a><?php endif ?>
        </address>
      </div>
      <nav aria-labelledby="pied-legal">
        <h2 class="site-footer__title" id="pied-legal"><?= e(t('pied.informations_legales')) ?></h2>
        <ul>
          <?php foreach ($legal as $href => $cle): ?><li><a href="<?= e(lurl((string) $href)) ?>"><?= e(t((string) $cle)) ?></a></li><?php endforeach ?>
        </ul>
      </nav>
      <div>
        <h2 class="site-footer__title"><?= e(t('langue.choisir')) ?></h2>
        <?php partial('langue/selecteur', ['chemin' => $path, 'disponibles' => $disponibles, 'variante' => 'liste', 'id' => 'langues-pied']) ?>
        <a class="site-footer__haut" href="#haut"><svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true" focusable="false"><path d="M12 19V5M5 12l7-7 7 7" fill="none" stroke="currentColor" stroke-width="2.25" stroke-linecap="round" stroke-linejoin="round"/></svg><?= e(t('site.haut')) ?></a>
      </div>
    </div>
    <div class="site-footer__bottom">
      <p>© <?= date('Y') ?> <?= e($name) ?></p>
      <?php $c = (array) Site::get('credit', []); if ($c !== []): ?>
      <p class="site-footer__credit"><?= e($c['avant']) ?><a href="<?= e($c['lien']['url']) ?>" rel="noopener"><?= e($c['lien']['texte']) ?></a><?= e($c['apres']) ?><?php if (!empty($c['complement'])): ?><?= e($c['complement'][0]) ?><a href="<?= e($c['complement'][1]['url']) ?>" rel="noopener"><?= e($c['complement'][1]['texte']) ?></a><?php endif ?><?= e($c['fin']) ?></p>
      <?php endif ?>
    </div>
  </div>
</footer>
</body>
</html>
