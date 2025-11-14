<?php

declare(strict_types=1);

namespace App\Modules\Galerie;

use App\Modules\Galerie\Dto\GalerieDTO;
use Capsule\Support\Pagination\Paginator;

/**
 * GalerieService
 * 
 * Orchestration métier autour de la galerie.
 * - Gère la pagination des images (24 par page).
 * - Retourne les images paginées et les informations de pagination.
 */
final class GalerieService
{
    private const IMAGES_PER_PAGE = 24;

    public function __construct(private readonly GalerieRepository $repository)
    {
    }

    /**
     * Récupère toutes les images avec pagination
     * 
     * @return iterable<GalerieDTO>
     */
    public function getAllImages(): iterable
    {
        return $this->repository->getAllImages();
    }

    /**
     * Compte le nombre total d'images
     */
    public function countAllImages(): int
    {
        return $this->repository->countImages();
    }

    /**
     * Récupère les images avec offset et limit
     * 
     * @return iterable<GalerieDTO>
     */
    public function getImagePage(int $limit, int $offset): iterable
    {
        $count = 0;
        $skipped = 0;

        foreach ($this->repository->getAllImages() as $image) {
            // Sauter les N premiers (offset)
            if ($skipped < $offset) {
                $skipped++;
                continue;
            }

            // Arrêter après limit images
            if ($count >= $limit) {
                break;
            }

            yield $image;
            $count++;
        }
    }
}
