<?php

declare(strict_types=1);

namespace App\Lang;

/**
 * Classe utilitaire pour charger un ensemble standardisé de chaînes de traduction multilingue.
 *
 * @package App\Lang
 */
class TranslationLoader
{
    /**
     * Charge dynamiquement toutes les chaînes de traduction nécessaires à une vue complète.
     *
     * des clés/valeurs disponibles, en ajoutant la langue courante sous la clé 'lang'.
     *
     * @param string $defaultLang Langue par défaut en fallback (ex: 'fr').
     * @return array<string,string>
     */
    public static function load(string $defaultLang = 'fr'): array
    {
        $lang = Translate::detectAndLoad($defaultLang);
        $out = Translate::all();           // cartes déjà chargées
        $out['lang'] = $lang;

        return $out;
    }
}
