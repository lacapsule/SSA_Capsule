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

    /**
     * Upload une ou plusieurs images
     * 
     * @param array<int, array{tmp_name:string,name:string,type:string,error:int,size:int}> $files
     * @param array<int, string|null> $customNames Noms personnalisés optionnels pour chaque image
     * @return array{success: int, errors: array<int, string>}
     */
    public function uploadImages(array $files, array $customNames = []): array
    {
        $success = 0;
        $errors = [];

        foreach ($files as $index => $file) {
            if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                $errors[$index] = 'Erreur lors de l\'upload du fichier';
                continue;
            }

            $customName = $customNames[$index] ?? null;
            $filename = \App\Support\ImageConverter::convertUploadedFileForGallery(
                $file,
                $customName,
                $this->repository->getGalleryPath()
            );

            if ($filename === null) {
                $errors[$index] = 'Impossible de convertir l\'image';
                continue;
            }

            $success++;
        }

        return ['success' => $success, 'errors' => $errors];
    }

    /**
     * Supprime une image
     */
    public function deleteImage(string $filename): bool
    {
        return $this->repository->deleteImage($filename);
    }

    /**
     * Supprime plusieurs images
     * 
     * @param array<int, string> $filenames
     * @return array{success: int, failed: array<int, string>}
     */
    public function deleteImages(array $filenames): array
    {
        $success = 0;
        $failed = [];

        foreach ($filenames as $index => $filename) {
            if ($this->repository->deleteImage($filename)) {
                $success++;
            } else {
                $failed[$index] = $filename;
            }
        }

        return ['success' => $success, 'failed' => $failed];
    }

    /**
     * Renomme une image
     */
    public function renameImage(string $oldFilename, string $newFilename): bool
    {
        return $this->repository->renameImage($oldFilename, $newFilename);
    }
}
