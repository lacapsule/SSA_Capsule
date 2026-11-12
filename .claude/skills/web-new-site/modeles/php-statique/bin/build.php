<?php
// SPDX-License-Identifier: GPL-3.0-or-later
// Construit le site statique dans dist/ : php bin/build.php
// dist/ est entièrement régénéré : ne jamais y modifier un fichier à la main.
declare(strict_types=1);

require __DIR__ . '/../src/lib/routes.php';

// Le moindre avertissement PHP (clé manquante, fichier absent…) arrête le build.
set_error_handler(function (int $niveau, string $message, string $fichier, int $ligne): never {
    throw new ErrorException($message, 0, $niveau, $fichier, $ligne);
});

$dist = ROOT . '/dist';

function vider(string $dossier): void
{
    if (!is_dir($dossier)) {
        return;
    }
    $iter = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dossier, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($iter as $f) {
        $f->isDir() ? rmdir($f->getPathname()) : unlink($f->getPathname());
    }
    rmdir($dossier);
}

function ecrire(string $fichier, string $contenu): void
{
    is_dir(dirname($fichier)) || mkdir(dirname($fichier), 0775, true);
    file_put_contents($fichier, $contenu);
}

// lastmod : dernier commit de la page ou de son contenu (src/content/*/<slug>.md), jamais la date du build.
function date_contenu(string $fichier, ?string $param): ?string
{
    $sources = [$fichier];
    if ($param !== null) {
        $sources = array_merge($sources, glob(ROOT . '/src/content/*/' . $param . '.md') ?: []);
    }
    $dates = [];
    foreach ($sources as $source) {
        $sortie = shell_exec('git -C ' . escapeshellarg(ROOT) . ' log -1 --format=%cs -- ' . escapeshellarg($source) . ' 2>/dev/null');
        if (is_string($sortie) && preg_match('/^\d{4}-\d{2}-\d{2}$/', trim($sortie))) {
            $dates[] = trim($sortie);
        }
    }
    return $dates ? max($dates) : null;
}

vider($dist);

// 1. public/ copié tel quel
$public = ROOT . '/public';
$iter = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($public, FilesystemIterator::SKIP_DOTS));
foreach ($iter as $f) {
    $cible = $dist . substr($f->getPathname(), strlen($public));
    is_dir(dirname($cible)) || mkdir(dirname($cible), 0775, true);
    copy($f->getPathname(), $cible);
}

// 2. CSS avec empreinte dans le nom (cache 1 an « immutable » ; forme nom.<hash>.css attendue par le .htaccess)
$css = css_bundle();
$GLOBALS['SITE_CSS'] = '/assets/site.' . substr(hash('sha256', $css), 0, 10) . '.css';
ecrire($dist . $GLOBALS['SITE_CSS'], $css);

// 3. Pages
$routes = routes();
foreach ($routes as $chemin => [$fichier, $param]) {
    ecrire($dist . $chemin . 'index.html', render($fichier, $param, $chemin));
}
ecrire("$dist/404.html", render(ROOT . '/src/pages/404.php', null, '/404'));

// 4. Fichiers pour les moteurs et assistants (rule web-seo, « Indexation » et « SEO passif »)
// Indexable ⇔ dans le sitemap : une page rendue avec noindex n'y entre pas.
$site = data('site');
$indexables = [];
foreach ($routes as $chemin => [$fichier, $param]) {
    $html = file_get_contents($dist . $chemin . 'index.html');
    if (!preg_match('#<meta name="robots" content="[^"]*noindex#', $html)) {
        $indexables[$chemin] = [$fichier, $param, $html];
    }
}

$sitemap = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n";
foreach ($indexables as $chemin => [$fichier, $param]) {
    $date = date_contenu($fichier, $param);
    $sitemap .= '  <url><loc>' . e(url_absolue($chemin)) . '</loc>' . ($date ? "<lastmod>$date</lastmod>" : '') . "</url>\n";
}
ecrire("$dist/sitemap.xml", $sitemap . "</urlset>\n");
ecrire("$dist/robots.txt", "User-agent: *\nContent-Signal: {$site['contentSignal']}\nAllow: /\n\nSitemap: " . url_absolue('/sitemap.xml') . "\n");

// llms.txt : mêmes faits que site.json et que les pages, rien d'écrit en dur.
$llms = "# {$site['nom']}\n\n> {$site['description']}\n\n"
    . "Zone : {$site['zone']}. Contact : {$site['contact']['email']}, {$site['contact']['telephoneAffiche']}.\n\n## Pages\n\n";
foreach ($indexables as $chemin => [, , $html]) {
    preg_match('#<title>(.*?)</title>#s', $html, $t);
    preg_match('#<meta name="description" content="([^"]*)"#', $html, $d);
    $llms .= '- [' . html_entity_decode($t[1] ?? $chemin) . '](' . url_absolue($chemin) . ')'
        . (isset($d[1]) ? ': ' . html_entity_decode($d[1]) : '') . "\n";
}
ecrire("$dist/llms.txt", $llms);

printf("✅ %d pages construites dans dist/ (+ 404, sitemap.xml, robots.txt, llms.txt)\n", count($routes));
