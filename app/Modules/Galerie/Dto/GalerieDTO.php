<?php

declare(strict_types=1);

namespace App\Modules\Galerie\Dto;

/**
 * GalerieDTO
 * 
 * Représente une image de la galerie avec son chemin et ses métadonnées.
 */
final class GalerieDTO
{
    public function __construct(
        public readonly string $src,
        public readonly string $alt
    ) {
    }
}
