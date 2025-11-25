<?php

declare(strict_types=1);

namespace App\Modules\Galerie;

use App\Modules\Galerie\Dto\GalerieDTO;
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
    private static function buildPagination(Page $page): array
    {
        $totalPages = $page->pages();
        $currentPage = max(1, min($totalPages, $page->page));

        $pages = [];
        for ($i = 1; $i <= $totalPages; $i++) {
            $pages[] = [
                'number' => $i,
                'isCurrent' => $i === $currentPage,
            ];
        }

        return [
            'current' => $currentPage,
            'total' => $totalPages,
            'hasPrev' => $page->hasPrev(),
            'hasNext' => $page->hasNext(),
            'prev' => max(1, $currentPage - 1),
            'next' => min($totalPages, $currentPage + 1),
            'first' => 1,
            'last' => $totalPages,
            'hasFirst' => $currentPage > 1,
            'hasLast' => $currentPage < $totalPages,
            'pages' => $pages,
            'showPagination' => $totalPages >= 1,
        ];
    }

    /**
     * Prépare les données pour le template de galerie
     * 
     * @param iterable<GalerieDTO> $images
     * @param Page $page
     * @param array<string,mixed> $base
     * @return array<string,mixed>
     */
    public static function index(iterable $images, Page $page, array $base = []): array
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
            'pagination' => self::buildPagination($page),
            ...$base,
        ];
    }
}
