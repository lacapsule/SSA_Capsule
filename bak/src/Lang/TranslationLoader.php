<?php

declare(strict_types=1);

namespace App\Lang;

use App\Lang\Translate;

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
     * Utilise `Translate::detect_and_load()` en amont puis renvoie l’ensemble
     * des clés/valeurs disponibles, en ajoutant la langue courante sous la clé 'lang'.
     *
     * @param string $defaultLang Langue par défaut en fallback (ex: 'fr').
     * @return array<string,string>
     */
    public static function load(string $defaultLang = 'fr'): array
    {
        Translate::detect_and_load($defaultLang);
        /** @var array<string,string> $out */
        $out = Translate::all();
        $out['lang'] = $_SESSION['lang'] ?? $defaultLang;
        return $out;
    }
}
