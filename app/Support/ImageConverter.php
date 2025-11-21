<?php

declare(strict_types=1);

namespace App\Support;

final class ImageConverter
{
    /**
     * Convertit un fichier uploadé en WebP et retourne le chemin web (ex: /assets/img/articles/20251114-1.webp)
     * Retourne null en cas d'erreur.
     *
     * @param array{tmp_name:string,name:string,type:string,error:int,size:int} $file
     * @param string|null $destDirAbsolute Chemin absolu vers le dossier de destination (optionnel)
     * @param int $quality Qualité WebP 0-100
     * @return string|null
     */
    public static function convertUploadedFile(array $file, ?string $destDirAbsolute = null, int $quality = 80): ?string
    {
        $basePath = __DIR__ . '/../../';
        $destDir = $destDirAbsolute ?? ($basePath . 'public/assets/img/articles');
        if (!is_dir($destDir)) {
            @mkdir($destDir, 0755, true);
        }

        $tmp = $file['tmp_name'] ?? null;
        if (!$tmp || !is_file($tmp)) {
            return null;
        }

        $info = @getimagesize($tmp);
        $mime = $info['mime'] ?? '';

        // Charger l'image source
        $src = null;
        if ($mime === 'image/jpeg' || $mime === 'image/jpg') {
            $src = @imagecreatefromjpeg($tmp);
        } elseif ($mime === 'image/png') {
            $src = @imagecreatefrompng($tmp);
        } elseif ($mime === 'image/gif') {
            $src = @imagecreatefromgif($tmp);
        } elseif ($mime === 'image/webp') {
            $src = @imagecreatefromwebp($tmp);
        } else {
            // Type non supporté
            return null;
        }

        if (!$src) {
            return null;
        }

        $width = imagesx($src);
        $height = imagesy($src);

        // Créer un canvas en vraies couleurs
        $dst = imagecreatetruecolor($width, $height);
        // Préserver la transparence pour PNG/GIF
        if (in_array($mime, ['image/png', 'image/gif', 'image/webp'], true)) {
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
            $transparent = imagecolorallocatealpha($dst, 255, 255, 255, 127);
            imagefilledrectangle($dst, 0, 0, $width, $height, $transparent);
        }

        imagecopyresampled($dst, $src, 0, 0, 0, 0, $width, $height, $width, $height);

        // Générer le nom de fichier: YYYYMMDD-N
        $prefix = date('Ymd');
        $existing = glob($destDir . '/' . $prefix . '-*.webp');
        $num = is_array($existing) ? count($existing) + 1 : 1;
        $filename = sprintf('%s-%d.webp', $prefix, $num);
        $destPath = $destDir . '/' . $filename;

        // Enregistrer en WebP
        $saved = @imagewebp($dst, $destPath, $quality);

        // Libérer les ressources
        imagedestroy($src);
        imagedestroy($dst);

        if ($saved) {
            // Return web path
            return '/assets/img/articles/' . $filename;
        }

        return null;
    }

    /**
     * Convertit un fichier uploadé en WebP pour la galerie avec un nom personnalisé
     * Retourne le filename (sans chemin) ou null en cas d'erreur.
     *
     * @param array{tmp_name:string,name:string,type:string,error:int,size:int} $file
     * @param string|null $customName Nom personnalisé (sans extension)
     * @param string|null $destDirAbsolute Chemin absolu vers le dossier de destination (optionnel)
     * @param int $quality Qualité WebP 0-100
     * @return string|null Filename (ex: "mon-image.webp")
     */
    public static function convertUploadedFileForGallery(
        array $file,
        ?string $customName = null,
        ?string $destDirAbsolute = null,
        int $quality = 80
    ): ?string {
        $basePath = __DIR__ . '/../../';
        $destDir = $destDirAbsolute ?? ($basePath . 'public/assets/img/gallery');
        
        if (!is_dir($destDir)) {
            @mkdir($destDir, 0755, true);
        }

        $tmp = $file['tmp_name'] ?? null;
        if (!$tmp || !is_file($tmp)) {
            return null;
        }

        $info = @getimagesize($tmp);
        $mime = $info['mime'] ?? '';

        // Charger l'image source
        $src = null;
        if ($mime === 'image/jpeg' || $mime === 'image/jpg') {
            $src = @imagecreatefromjpeg($tmp);
        } elseif ($mime === 'image/png') {
            $src = @imagecreatefrompng($tmp);
        } elseif ($mime === 'image/gif') {
            $src = @imagecreatefromgif($tmp);
        } elseif ($mime === 'image/webp') {
            $src = @imagecreatefromwebp($tmp);
        } else {
            return null;
        }

        if (!$src) {
            return null;
        }

        $width = imagesx($src);
        $height = imagesy($src);

        // Créer un canvas en vraies couleurs
        $dst = imagecreatetruecolor($width, $height);
        // Préserver la transparence pour PNG/GIF
        if (in_array($mime, ['image/png', 'image/gif', 'image/webp'], true)) {
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
            $transparent = imagecolorallocatealpha($dst, 255, 255, 255, 127);
            imagefilledrectangle($dst, 0, 0, $width, $height, $transparent);
        }

        imagecopyresampled($dst, $src, 0, 0, 0, 0, $width, $height, $width, $height);

        // Générer le nom de fichier
        if ($customName !== null && $customName !== '') {
            // Nettoyer le nom personnalisé
            $cleanName = preg_replace('/[^a-zA-Z0-9_-]/', '-', $customName);
            $cleanName = preg_replace('/-+/', '-', $cleanName);
            $cleanName = trim($cleanName, '-');
            if ($cleanName === '') {
                $cleanName = 'image';
            }
            $filename = $cleanName . '.webp';
        } else {
            // Générer un nom automatique: YYYYMMDD-HHMMSS-N
            $prefix = date('Ymd-His');
            $existing = glob($destDir . '/' . $prefix . '-*.webp');
            $num = is_array($existing) ? count($existing) + 1 : 1;
            $filename = sprintf('%s-%d.webp', $prefix, $num);
        }

        // Vérifier si le fichier existe déjà et ajouter un suffixe si nécessaire
        $destPath = $destDir . '/' . $filename;
        $counter = 1;
        while (is_file($destPath)) {
            $pathInfo = pathinfo($filename);
            $filename = $pathInfo['filename'] . '-' . $counter . '.webp';
            $destPath = $destDir . '/' . $filename;
            $counter++;
        }

        // Enregistrer en WebP
        $saved = @imagewebp($dst, $destPath, $quality);

        // Libérer les ressources
        imagedestroy($src);
        imagedestroy($dst);

        if ($saved) {
            return $filename;
        }

        return null;
    }
}
