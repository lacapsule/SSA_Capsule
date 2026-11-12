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

            $images[] = [
                'filename' => $file,
                'mtime' => @filemtime($filePath) ?: 0,
            ];
        }

        // Trier du plus récent au plus ancien (fallback ordre naturel si égalité)
        usort($images, static function (array $a, array $b): int {
            if ($a['mtime'] === $b['mtime']) {
                return strnatcmp($a['filename'], $b['filename']);
            }

            return $b['mtime'] <=> $a['mtime'];
        });

        foreach ($images as $image) {
            $filename = $image['filename'];
            yield new GalerieDTO(
                src: '/assets/img/gallery/' . $filename,
                alt: pathinfo($filename, PATHINFO_FILENAME),
                filename: $filename
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

    /**
     * Récupère le chemin absolu du dossier galerie
     */
    public function getGalleryPath(): string
    {
        return $this->basePath . self::GALLERY_PATH;
    }

    /**
     * Supprime une image par son filename
     */
    public function deleteImage(string $filename): bool
    {
        $galleryPath = $this->getGalleryPath();
        $filePath = $galleryPath . '/' . $filename;

        // Sécurité : vérifier que le fichier est bien dans le dossier galerie
        $realPath = realpath($filePath);
        $realGalleryPath = realpath($galleryPath);
        
        if (!$realPath || !$realGalleryPath || !str_starts_with($realPath, $realGalleryPath)) {
            return false;
        }

        if (is_file($realPath)) {
            return @unlink($realPath);
        }

        return false;
    }

    /**
     * Renomme une image
     */
    public function renameImage(string $oldFilename, string $newFilename): bool
    {
        $galleryPath = $this->getGalleryPath();
        $oldPath = $galleryPath . '/' . $oldFilename;
        $newPath = $galleryPath . '/' . $newFilename;

        // Sécurité : vérifier que les fichiers sont bien dans le dossier galerie
        $realOldPath = realpath($oldPath);
        $realNewPath = realpath($galleryPath);
        
        if (!$realOldPath || !$realNewPath || !str_starts_with($realOldPath, $realNewPath)) {
            return false;
        }

        // Vérifier que le nouveau nom n'existe pas déjà
        if (is_file($newPath)) {
            return false;
        }

        // Vérifier l'extension
        $oldExt = strtolower(pathinfo($oldFilename, PATHINFO_EXTENSION));
        $newExt = strtolower(pathinfo($newFilename, PATHINFO_EXTENSION));
        
        if ($oldExt !== $newExt || !in_array($newExt, self::ALLOWED_EXTENSIONS, true)) {
            return false;
        }

        return @rename($realOldPath, $newPath);
    }

    /**
     * Vérifie si un fichier existe
     */
    public function imageExists(string $filename): bool
    {
        $galleryPath = $this->getGalleryPath();
        $filePath = $galleryPath . '/' . $filename;
        return is_file($filePath);
    }
}
