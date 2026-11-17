<?php
declare(strict_types=1);

namespace App\Site;

/** Mise en forme des textes saisis (sans mbstring : expressions régulières UTF-8). */
final class Texte
{
    /** Coupe au dernier mot entier sous $max caractères, suivi de « … ». */
    public static function couper(string $texte, int $max): string
    {
        $texte = trim((string) preg_replace('/\s+/u', ' ', $texte));
        if (text_length($texte) <= $max) {
            return $texte;
        }
        preg_match('/^.{0,' . ($max - 1) . '}(?=\s)/su', $texte, $m);
        return rtrim($m[0] ?? '', ' ,;:') . '…';
    }

    /** Texte brut en paragraphes HTML échappés (une ligne vide ou un retour = un paragraphe). */
    public static function paragraphes(string $texte): string
    {
        $blocs = preg_split('/\n+/u', trim($texte)) ?: [];
        return implode("\n", array_map(static fn (string $p): string => '<p>' . e(trim($p)) . '</p>', array_filter($blocs, static fn (string $p): bool => trim($p) !== '')));
    }

    /**
     * Texte d'article saisi en lignes simples, mis en forme sans rien ajouter : une ligne coupée au milieu d'une phrase
     * (suite en minuscule) est recollée ; une ligne qui finit par « : » suivie de lignes courtes devient une liste ; une
     * ligne courte sans ponctuation finale, suivie d'un paragraphe, devient un intertitre ; le premier paragraphe est le
     * chapeau (classe article__attaque), traité de la même façon dans tous les articles.
     */
    public static function article(string $texte): string
    {
        $finie = static fn (string $l): bool => preg_match('/[.!?:…»)]$/u', $l) === 1;
        // Ligne en retrait ou à puce dans la saisie : élément de liste, jamais recollé.
        // Ligne saisie avec une espace finale : retour à la ligne voulu, la suivante n'y est pas recollée.
        $recollees = [];
        $puces = [];
        $voulu = [];
        foreach (preg_split('/\R/u', $texte) ?: [] as $brute) {
            $l = trim((string) preg_replace('/^\s*[-•*]\s+/u', '', $brute));
            if ($l === '') {
                continue;
            }
            $puce = preg_match('/^(\s+|\s*[-•*]\s)/u', $brute) === 1;
            $n = count($recollees);
            if ($n > 0 && !$puce && !$puces[$n - 1] && !$voulu[$n - 1] && preg_match('/^\p{Ll}/u', $l) === 1 && !str_ends_with($recollees[$n - 1], ':')) {
                $recollees[$n - 1] .= ' ' . $l;
                $voulu[$n - 1] = preg_match('/\h$/u', $brute) === 1;
            } else {
                $recollees[] = $l;
                $puces[] = $puce;
                $voulu[] = preg_match('/\h$/u', $brute) === 1;
            }
        }
        $html = [];
        $premier = true;
        $attaque = static fn (string $l): string => ' class="article__attaque"'; // même traitement pour tous les chapeaux
        for ($i = 0, $n = count($recollees); $i < $n; $i++) {
            $l = $recollees[$i];
            if (str_ends_with($l, ':') && $i + 1 < $n) {
                $items = [];
                while ($i + 1 < $n && mb_strlen($recollees[$i + 1]) <= 140 && !str_ends_with($recollees[$i + 1], ':')) {
                    $items[] = '<li>' . e(rtrim($recollees[++$i], ' ;,.')) . '</li>';
                }
                $html[] = '<p' . ($premier ? $attaque($l) : '') . '>' . e($l) . '</p>';
                if ($items !== []) {
                    $html[] = '<ul class="article__liste">' . implode('', $items) . '</ul>';
                }
                $premier = false;
                continue;
            }
            if (!$premier && !$finie($l) && mb_strlen($l) <= 50 && $i + 1 < $n) {
                $html[] = '<h2 class="article__intertitre">' . e($l) . '</h2>';
                continue;
            }
            $html[] = '<p' . ($premier ? $attaque($l) : '') . '>' . e($l) . '</p>';
            $premier = false;
        }
        return implode("\n", $html);
    }

    /** Texte échappé dont chaque adresse e-mail devient un lien mailto (textes légaux saisis en configuration). */
    public static function courriels(string $texte): string
    {
        $morceaux = preg_split('/([A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,})/u', $texte, -1, PREG_SPLIT_DELIM_CAPTURE) ?: [$texte];
        $html = '';
        foreach ($morceaux as $i => $m) {
            $html .= $i % 2 === 1 ? '<a href="mailto:' . e($m) . '">' . e($m) . '</a>' : e($m);
        }
        return $html;
    }
}
