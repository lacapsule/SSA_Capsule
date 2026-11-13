<?php
declare(strict_types=1);

/**
 * Bas de fiche : cartes précédent et suivant, puis retour à la rubrique.
 * $precedent, $suivant : null ou ['url' => string, 'titre' => string, 'vignette' => ?string (adresse d'image)].
 * $libelles : ['nav' => 'Autres articles', 'precedent' => 'Article précédent', 'suivant' => 'Article suivant',
 *              'retour' => 'Toutes les actualités', 'urlRetour' => '/actualites/'].
 * Échappement : e() du socle (htmlspecialchars). Rien n'est affiché sans voisin ni retour.
 */
function navigation_fiches(?array $precedent, ?array $suivant, array $libelles): string
{
    if ($precedent === null && $suivant === null && empty($libelles['urlRetour'])) {
        return '';
    }
    $fleche = static fn (string $d): string => '<svg class="navigation-fiches__fleche" viewBox="0 0 16 16" width="1.25em" height="1.25em" aria-hidden="true" focusable="false"><path d="' . $d . '" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>';
    $vignette = static fn (?string $src): string => '<span class="navigation-fiches__vignette" aria-hidden="true">'
        . ($src !== null && $src !== '' ? '<img src="' . e($src) . '" alt="" width="144" height="108" loading="lazy" decoding="async">' : '') . '</span>';
    $texte = static fn (string $sens, string $titre): string => '<span class="navigation-fiches__texte"><span class="navigation-fiches__sens">'
        . e($sens) . '</span><span class="navigation-fiches__titre">' . e($titre) . '</span></span>';
    $html = '<nav class="navigation-fiches" aria-label="' . e($libelles['nav'] ?? 'Autres fiches') . '"><ul class="navigation-fiches__liste">';
    if ($precedent !== null) {
        $html .= '<li class="navigation-fiches__element navigation-fiches__element--precedent"><a class="navigation-fiches__carte" href="' . e($precedent['url']) . '" rel="prev">'
            . $fleche('M10 3.5 5.5 8l4.5 4.5') . $vignette($precedent['vignette'] ?? null) . $texte($libelles['precedent'] ?? 'Précédent', $precedent['titre']) . '</a></li>';
    }
    if ($suivant !== null) {
        $html .= '<li class="navigation-fiches__element navigation-fiches__element--suivant"><a class="navigation-fiches__carte" href="' . e($suivant['url']) . '" rel="next">'
            . $texte($libelles['suivant'] ?? 'Suivant', $suivant['titre']) . $vignette($suivant['vignette'] ?? null) . $fleche('M6 3.5 10.5 8 6 12.5') . '</a></li>';
    }
    $html .= '</ul>';
    if (!empty($libelles['urlRetour'])) {
        $html .= '<p class="navigation-fiches__retour"><a href="' . e($libelles['urlRetour']) . '">' . e($libelles['retour'] ?? 'Retour à la liste') . '</a></p>';
    }
    return $html . '</nav>';
}
