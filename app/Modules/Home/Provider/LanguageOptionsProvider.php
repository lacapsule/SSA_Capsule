<?php

declare(strict_types=1);

namespace App\Modules\Home\Provider;

/**
 * LanguageOptionsProvider
 * - Construit la liste des langues pour l'UI à partir de l'état i18n.
 * - Zéro détection ici : le middleware/contrôleur fournit $currentLang et $i18n.
 */
final class LanguageOptionsProvider
{
    /**
     * @param array<string,string> $i18n  (ex: Translate::all() + ['lang'=>'fr'])
     * @return array<int,array{code:string,label:string,selected:bool}>
     */
    public static function make(array $i18n, string $currentLang = 'fr'): array
    {
        $langs = [
            ['code' => 'fr','label' => $i18n['lang_fr'] ?? 'Français'],
            ['code' => 'br','label' => $i18n['lang_br'] ?? 'Brezhoneg'],
        ];
        foreach ($langs as &$x) {
            $x['selected'] = ($x['code'] === $currentLang);
        }

        return $langs;
    }
}
