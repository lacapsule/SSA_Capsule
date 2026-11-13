<?php
declare(strict_types=1);

namespace App\Langue;

/**
 * Catégorie de pluriel d'un nombre selon les règles CLDR (zero, one, two, few, many, other), pour les langues
 * courantes. Le français met 0 et 1 au singulier (« 0 résultat »), l'anglais seulement 1 (« 0 results »).
 * Langue absente de la table : one pour 1, other sinon. Ajouter une langue : une branche du match.
 */
final class Pluriel
{
    /** @return list<string> catégories employées par la langue, dans l'ordre CLDR (contrôle des catalogues) */
    public static function categories(string $langue): array
    {
        return match (self::base($langue)) {
            'ja', 'zh', 'ko', 'vi', 'th', 'id', 'ms' => ['other'],
            'fr', 'pt', 'it', 'es', 'ca' => ['one', 'many', 'other'],
            'ru', 'uk', 'pl' => ['one', 'few', 'many', 'other'],
            'cs', 'sk' => ['one', 'few', 'many', 'other'],
            'ar' => ['zero', 'one', 'two', 'few', 'many', 'other'],
            'he' => ['one', 'two', 'other'],
            default => ['one', 'other'],
        };
    }

    /** Catégories qu'un catalogue doit fournir au minimum (« many » des langues romanes ne sert qu'aux millions). @return list<string> */
    public static function requises(string $langue): array
    {
        return array_values(array_diff(self::categories($langue), in_array(self::base($langue), ['fr', 'pt', 'it', 'es', 'ca'], true) ? ['many'] : []));
    }

    public static function categorie(string $langue, int|float $nombre): string
    {
        $n = abs($nombre);
        $entier = (float) (int) $n === (float) $n;
        $i = (int) $n;
        $v = $entier ? 0 : strlen(rtrim(substr(sprintf('%.6F', $n), strpos(sprintf('%.6F', $n), '.') + 1), '0'));
        $mod10 = $i % 10;
        $mod100 = $i % 100;

        return match (self::base($langue)) {
            'ja', 'zh', 'ko', 'vi', 'th', 'id', 'ms' => 'other',
            // Français : 0 et 1 (et 1,5) au singulier ; « many » pour les millions ronds (« 1 million de »).
            'fr' => $i === 0 || $i === 1 ? 'one' : ($entier && $i !== 0 && $i % 1000000 === 0 ? 'many' : 'other'),
            'pt' => $i === 0 || $i === 1 ? 'one' : ($entier && $i % 1000000 === 0 ? 'many' : 'other'),
            'it', 'es', 'ca' => $i === 1 && $v === 0 ? 'one' : ($entier && $i !== 0 && $i % 1000000 === 0 ? 'many' : 'other'),
            'ru', 'uk' => $v !== 0 ? 'other' : match (true) {
                $mod10 === 1 && $mod100 !== 11 => 'one',
                $mod10 >= 2 && $mod10 <= 4 && ($mod100 < 12 || $mod100 > 14) => 'few',
                default => 'many',
            },
            'pl' => $v !== 0 ? 'other' : match (true) {
                $i === 1 => 'one',
                $mod10 >= 2 && $mod10 <= 4 && ($mod100 < 12 || $mod100 > 14) => 'few',
                default => 'many',
            },
            'cs', 'sk' => $v !== 0 ? 'many' : ($i === 1 ? 'one' : ($i >= 2 && $i <= 4 ? 'few' : 'other')),
            'ar' => !$entier ? 'other' : match (true) {
                $i === 0 => 'zero',
                $i === 1 => 'one',
                $i === 2 => 'two',
                $mod100 >= 3 && $mod100 <= 10 => 'few',
                $mod100 >= 11 && $mod100 <= 99 => 'many',
                default => 'other',
            },
            'he' => $i === 1 && $v === 0 ? 'one' : ($i === 2 && $v === 0 ? 'two' : 'other'),
            default => $i === 1 && $v === 0 ? 'one' : 'other',
        };
    }

    private static function base(string $langue): string
    {
        return strtolower(explode('-', str_replace('_', '-', $langue))[0]);
    }
}
