<?php

declare(strict_types=1);

namespace App\Modules\Galerie;

use App\Modules\Galerie\Dto\GalerieDTO;

/**
 * GalerieRepository
 * 
 * Responsable de la lecture des images depuis le système de fichiers.
 */
final class GalerieRepository
{
    private const GALLERY_PATH = '/public/assets/img/gallery';
    private const ALLOWED_EXTENSIONS = ['webp', 'jpg', 'jpeg', 'png', 'gif'];

    public function __construct(
        private readonly string $basePath = __DIR__ . '/../../..'
    ) {
    }

    /**
     * Récupère toutes les images de la galerie
     * 
     * @return iterable<GalerieDTO>
     */
    public function getAllImages(): iterable
    {
        $galleryPath = $this->basePath . self::GALLERY_PATH;

        if (!is_dir($galleryPath)) {
            return;
        }

        $files = scandir($galleryPath);
        if (!is_array($files)) {
            return;
        }

        // Filtrer et trier les fichiers
        $images = [];
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            if (!in_array($ext, self::ALLOWED_EXTENSIONS, true)) {
                continue;
            }

            $filePath = $galleryPath . '/' . $file;
            if (!is_file($filePath)) {
                continue;
            }

            $images[] = $file;
        }

        // Tri numérique naturel pour les noms comme image_1, image_2, etc.
        natsort($images);

        foreach ($images as $filename) {
            yield new GalerieDTO(
                src: '/assets/img/gallery/' . $filename,
                alt: pathinfo($filename, PATHINFO_FILENAME)
            );
        }
    }

    /**
     * Compte le nombre total d'images
     */
    public function countImages(): int
    {
        return iterator_count($this->getAllImages());
    }
}
