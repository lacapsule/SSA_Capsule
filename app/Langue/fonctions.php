<?php
declare(strict_types=1);

/*
 * Raccourcis pour les gabarits (modules/multilingue). À charger une fois, à la fin de app/bootstrap.php :
 *   require __DIR__ . '/Langue/fonctions.php';
 * Chaque fonction n'est déclarée que si le nom est libre.
 */

use App\Langue\Langue;
use App\Langue\T;

if (!function_exists('t')) {
    /** Texte d'interface traduit (texte brut : l'afficher avec e()). @param array<string, string|int|float> $params */
    function t(string $cle, array $params = []): string
    {
        return T::t($cle, $params);
    }
}

if (!function_exists('tn')) {
    /** Texte au pluriel : tn('recherche.resultats', 3). @param array<string, string|int|float> $params */
    function tn(string $cle, int|float $nombre, array $params = []): string
    {
        return T::n($cle, $nombre, $params);
    }
}

if (!function_exists('lurl')) {
    /** Lien vers une page dans la langue courante (ou une autre) depuis son chemin interne : lurl('/a-propos/'). */
    function lurl(string $chemin, ?string $langue = null): string
    {
        return Langue::url($chemin, $langue);
    }
}

if (!function_exists('langue_attr')) {
    /** Attribut lang=" " d'un champ resté dans une autre langue que la page (repli sur le français). */
    function langue_attr(array $ligne, string $champ): string
    {
        $l = \App\Langue\Contenu::langueDe($ligne, $champ);
        return $l !== \App\Langue\Langue::actuelle() ? ' lang="' . e($l) . '"' : '';
    }
}
