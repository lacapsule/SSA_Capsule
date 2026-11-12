<?php
// SPDX-License-Identifier: GPL-3.0-or-later
// Routage par fichiers, comme Astro : src/pages/a/b.php → /a/b/, src/pages/a/index.php → /a/.
// Page dynamique : nom entre crochets (src/pages/actions/[slug].php). Elle renvoie
// ['chemins' => fn() => [slugs…], 'page' => function (string $slug) { … }].
declare(strict_types=1);

require_once __DIR__ . '/site.php';

/** Toutes les URL du site → [fichier de page, paramètre ou null]. La page 404 n'en fait pas partie. */
function routes(): array
{
    $dossier = ROOT . '/src/pages';
    $routes = [];
    $fichiers = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dossier, FilesystemIterator::SKIP_DOTS));
    foreach ($fichiers as $f) {
        $relatif = substr($f->getPathname(), strlen($dossier), -4); // « /actions/[slug] »
        if ($f->getExtension() !== 'php' || $relatif === '/404') {
            continue;
        }
        $relatif = preg_replace('#/index$#', '', $relatif);
        if (preg_match('#^(.*)/\[\w+\]$#', $relatif, $m)) {
            $dynamique = require $f->getPathname();
            foreach (($dynamique['chemins'])() as $slug) {
                $routes["$m[1]/$slug/"] = [$f->getPathname(), $slug];
            }
        } else {
            $routes[$relatif . '/'] = [$f->getPathname(), null];
        }
    }
    ksort($routes);
    return $routes;
}

/** Rend une page et renvoie son HTML. */
function render(string $fichier, ?string $param, string $chemin): string
{
    $GLOBALS['SITE_CHEMIN'] = $chemin;
    ob_start();
    $page = require $fichier;
    if (is_array($page)) {
        ($page['page'])($param);
    }
    return ob_get_clean();
}

/**
 * Feuille du site : fichiers de src/styles assemblés dans l'ordre de src/styles/ordre.json (cascade).
 * Un fichier absent de la liste, listé deux fois ou introuvable arrête le rendu : rien n'est oublié en silence.
 */
function css_bundle(): string
{
    $dossier = ROOT . '/src/styles';
    $ordre = json_decode(file_get_contents("$dossier/ordre.json"), true, 512, JSON_THROW_ON_ERROR)['fichiers'];
    $presents = [];
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dossier, FilesystemIterator::SKIP_DOTS)) as $f) {
        if ($f->getExtension() === 'css') {
            $presents[] = substr($f->getPathname(), strlen($dossier) + 1);
        }
    }
    $doublons = array_keys(array_filter(array_count_values($ordre), fn($n) => $n > 1));
    $absents = array_diff($ordre, $presents);
    $oublies = array_diff($presents, $ordre);
    if ($doublons || $absents || $oublies) {
        throw new RuntimeException('src/styles/ordre.json : '
            . ($oublies ? 'non listé(s) : ' . implode(', ', $oublies) . ' ; ' : '')
            . ($absents ? 'introuvable(s) : ' . implode(', ', $absents) . ' ; ' : '')
            . ($doublons ? 'en double : ' . implode(', ', $doublons) : ''));
    }
    $css = '';
    foreach ($ordre as $fichier) {
        $css .= "/* $fichier */\n" . file_get_contents("$dossier/$fichier") . "\n";
    }
    return $css;
}

/** Adresse de la feuille de style : fixe en dev, nom haché au build (cache long). */
function css_href(): string
{
    return $GLOBALS['SITE_CSS'] ?? '/assets/site.css';
}
