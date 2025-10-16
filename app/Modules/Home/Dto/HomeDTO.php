<?php

declare(strict_types=1);

namespace App\Modules\Home\Dto;

/**
 * HomeDTO
 * - Conteneur typé pour transporter les données DOMAINE vers la couche présentation.
 * - Zéro logique (pas d'accès DB, pas de session), valeurs stables.
 */
final class HomeDTO
{
    /**
     * @param iterable<object> $articles
     * @param array<array{name:string,role:string,url:string,logo:string}> $partenaires
     * @param array<array{name:string,role:string,url:string,logo:string}> $financeurs
     */
    public function __construct(
        public readonly iterable $articles,
        public readonly array $partenaires,
        public readonly array $financeurs,
    ) {
    }
}
