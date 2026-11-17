<?php
declare(strict_types=1);

namespace App\Lieux;

/**
 * Regroupe les lieux par commune (ou marché nommé), d'après leur nom saisi sur la carte du collectif :
 * « Producteur - Marché de Morlaix » ou « Producteur - Morlaix ». Rien n'est inventé : la commune est lue dans le nom ;
 * un nom sans commune reconnaissable va dans le groupe « autres ». Graphies proches (accents, casse) fusionnées.
 */
final class Groupes
{
    /**
     * @param list<array<string, mixed>> $lieux
     * @return list<array{cle: string, commune: string, lieux: list<array<string, mixed>>}> groupes triés par commune,
     *         chaque lieu complété de « producteur » (nom sans la commune) et « marche » (vendu sur un marché)
     */
    public static function parCommune(array $lieux, string $autres): array
    {
        $lus = [];
        foreach ($lieux as $l) {
            $lus[] = $l + self::lire((string) $l['nom']);
        }
        // Communes connues (lues après « - ») : un nom sans tiret qui finit par l'une d'elles y est rangé.
        $connues = [];
        foreach ($lus as $l) {
            if ($l['commune'] !== '') {
                $connues[self::cle($l['commune'])] = $l['commune'];
            }
        }
        $groupes = [];
        foreach ($lus as $l) {
            $commune = $l['commune'];
            if ($commune === '') {
                // Dernier mot du nom (une commune s'écrit sans espace : « Pleyber-Christ ») comparé aux communes connues.
                $pos = strrpos($l['producteur'], ' ');
                $dernier = $pos === false ? '' : substr($l['producteur'], $pos + 1);
                if ($dernier !== '' && isset($connues[self::cle($dernier)])) {
                    $commune = $connues[self::cle($dernier)];
                    $l['producteur'] = rtrim(substr($l['producteur'], 0, (int) $pos), ' -');
                }
            }
            $cle = $commune === '' ? '~' : self::cle($commune);
            $groupes[$cle]['commune'] = $commune === '' ? $autres : ($groupes[$cle]['commune'] ?? $commune);
            $groupes[$cle]['lieux'][] = $l;
        }
        ksort($groupes, SORT_STRING);
        $sortie = [];
        foreach ($groupes as $cle => $g) {
            usort($g['lieux'], static fn (array $a, array $b): int => strcasecmp(self::cle((string) $a['producteur']), self::cle((string) $b['producteur'])) ?: (int) $a['marche'] <=> (int) $b['marche']);
            $sortie[] = ['cle' => $cle === '~' ? 'autres' : self::ancre($cle), 'commune' => (string) $g['commune'], 'lieux' => $g['lieux']];
        }
        return $sortie;
    }

    /** @return array{producteur: string, commune: string, marche: bool, vente: string} vente : « Marché de Roscoff » tel que saisi */
    private static function lire(string $nom): array
    {
        $pos = strrpos($nom, ' - ');
        if ($pos === false) {
            return ['producteur' => trim($nom), 'commune' => '', 'marche' => false, 'vente' => ''];
        }
        $producteur = trim(substr($nom, 0, $pos));
        $lieu = trim(substr($nom, $pos + 3));
        $marche = preg_match('/^March[ée]\b/iu', $lieu) === 1;
        $vente = $marche ? $lieu : '';
        // « Marché de X », « Marché d'été de X » : la commune X ; « Marché du Poder » reste un lieu nommé.
        if (preg_match("/^March[ée] (?:d'été )?(?:de |d')(.+)$/iu", $lieu, $m) === 1) {
            $lieu = $m[1];
        }
        return ['producteur' => $producteur, 'commune' => $lieu, 'marche' => $marche, 'vente' => $vente];
    }

    /** Clé de comparaison : minuscules sans accents, « St » écrit « saint ». */
    private static function cle(string $texte): string
    {
        $t = strtolower(strtr($texte, ['É' => 'e', 'È' => 'e', 'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e', 'à' => 'a', 'â' => 'a', 'î' => 'i', 'ï' => 'i', 'ô' => 'o', 'û' => 'u', 'ù' => 'u', 'ü' => 'u', 'ç' => 'c']));
        return (string) preg_replace('/\bst\b/', 'saint', trim($t));
    }

    /** Ancre d'une commune (même règle que les groupes) : sert à relier un contour de la carte à son groupe. */
    public static function ancrePour(string $commune): string
    {
        return self::ancre(self::cle($commune));
    }

    private static function ancre(string $cle): string
    {
        return 'commune-' . trim((string) preg_replace('/[^a-z0-9]+/', '-', $cle), '-');
    }
}
