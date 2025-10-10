<?php

declare(strict_types=1);

namespace Capsule\View;

use App\Lang\TranslationLoader;

/**
 * Trait pour la gestion des traductions
 * 
 * Fournit des méthodes communes pour charger et gérer les traductions
 * dans les contrôleurs.
 */
trait TranslationTrait
{
    /** @var array<string,string>|null Cache des traductions par requête */
    private ?array $translationCache = null;

    /**
     * Charge les traductions avec cache
     * 
     * @param string $defaultLang Langue par défaut (fr par défaut)
     * @return array<string,string> Tableau des traductions
     */
    protected function translations(string $defaultLang = 'fr'): array
    {
        return $this->translationCache ??= TranslationLoader::load(defaultLang: $defaultLang);
    }

    /**
     * Alias de translations() pour compatibilité
     * 
     * @return array<string,string> Tableau des traductions
     */
    protected function i18n(): array
    {
        return $this->translations();
    }

    /**
     * Alias de translations() pour compatibilité
     * 
     * @return array<string,string> Tableau des traductions
     */
    protected function strings(): array
    {
        return $this->translations();
    }

    /**
     * Alias de translations() pour compatibilité
     * 
     * @return array<string,string> Tableau des traductions
     */
    protected function str(): array
    {
        return $this->translations();
    }
}