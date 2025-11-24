<?php

declare(strict_types=1);

namespace App\Modules\Galerie;

use App\Modules\Galerie\Dto\GalerieDTO;
use App\Support\PaginationRenderer;
use Capsule\Support\Pagination\Page;
use Capsule\View\Presenter\IterablePresenter;

/**
 * GaleriePresenter
 * 
 * Projection des données de galerie vers le template.
 * Formate les images et les informations de pagination pour MiniMustache.
 */
final class GaleriePresenter
{
    /**
     * Prépare les données pour le template de galerie
     * 
     * @param iterable<GalerieDTO> $images
     * @param Page $page
     * @param array<string,mixed> $base
     * @return array<string,mixed>
     */
    /**
     * @param array<string,mixed> $base
     * @param array<string,mixed> $paginationOptions
     */
    public static function index(iterable $images, Page $page, array $base = [], array $paginationOptions = []): array
    {
        // Transformer les DTOs en array pour le template
        $mapped = IterablePresenter::map($images, function (GalerieDTO $img): array {
            return [
                'src' => $img->src,
                'alt' => $img->alt,
                'filename' => $img->filename,
            ];
        });

        // Matérialiser uniquement les images de la page actuelle
        $pictures = IterablePresenter::toArray($mapped);

        return [
            'pictures' => $pictures,
            'pagination' => PaginationRenderer::build($page, $paginationOptions),
            ...$base,
        ];
    }
}
