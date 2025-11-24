<?php

declare(strict_types=1);

namespace App\Support;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

final class ImageConverter
{
    private const SUPPORTED_MIMES = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    private const DEFAULT_QUALITY = 80;
    private const DEFAULT_MAX_WIDTH = 1920;

    /**
     * Convertit un fichier uploadé en WebP et retourne le chemin web (ex: /assets/img/articles/20251114-1.webp).
     *
     * @param array{tmp_name:string,name:string,type:string,error:int,size:int} $file
     */
    public static function convertUploadedFile(
        array $file,
        ?string $destDirAbsolute = null,
        int $quality = self::DEFAULT_QUALITY,
        ?int $maxWidth = self::DEFAULT_MAX_WIDTH
    ): ?string {
        $basePath = realpath(__DIR__ . '/../../') ?: dirname(__DIR__, 2);
        $destDir = $destDirAbsolute ?? ($basePath . '/public/assets/img/articles');
        self::ensureDirectory($destDir);

        $filename = self::generateSequentialWebpName($destDir);
        $savedPath = self::storeUploadedFile($file, $destDir, $filename, $quality, $maxWidth);

        return $savedPath ? self::toPublicPath($savedPath) : null;
    }

    /**
     * Convertit un fichier uploadé en WebP pour la galerie avec un nom personnalisé
     * et retourne uniquement le filename (ex: mon-image.webp).
     *
     * @param array{tmp_name:string,name:string,type:string,error:int,size:int} $file
     */
    public static function convertUploadedFileForGallery(
        array $file,
        int $lotNumber = 1,
        ?string $destDirAbsolute = null,
        int $quality = self::DEFAULT_QUALITY,
        ?int $maxWidth = self::DEFAULT_MAX_WIDTH
    ): ?string {
        $basePath = realpath(__DIR__ . '/../../') ?: dirname(__DIR__, 2);
        $destDir = $destDirAbsolute ?? ($basePath . '/public/assets/img/gallery');
        self::ensureDirectory($destDir);

        $filename = self::generateGalleryLotFilename($destDir, $lotNumber);

        $savedPath = self::storeUploadedFile($file, $destDir, $filename, $quality, $maxWidth);

        return $savedPath ? basename($savedPath) : null;
    }

    /**
     * Optimise toutes les images d'un dossier (et sous-dossiers) en place.
     *
     * @return array{directory:string,optimized:int,skipped:int,dry_run:bool}
     */
    public static function optimizeExistingImages(
        string $absoluteDir,
        int $quality = self::DEFAULT_QUALITY,
        ?int $maxWidth = self::DEFAULT_MAX_WIDTH,
        bool $dryRun = false
    ): array {
        $quality = self::normalizeQuality($quality);
        $absoluteDir = rtrim($absoluteDir, DIRECTORY_SEPARATOR);

        if (!is_dir($absoluteDir)) {
            return [
                'directory' => $absoluteDir,
                'optimized' => 0,
                'skipped' => 0,
                'dry_run' => $dryRun,
            ];
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($absoluteDir, FilesystemIterator::SKIP_DOTS)
        );

        $optimized = 0;
        $skipped = 0;

        /** @var \SplFileInfo $file */
        $lotCounters = [];

        foreach ($iterator as $file) {
            if (!$file->isFile()) {
                continue;
            }

            $ext = strtolower($file->getExtension());
            if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) {
                continue;
            }

            $absolutePath = $file->getPathname();
            $result = self::reencodeExistingFile($absolutePath, $quality, $maxWidth, $dryRun);

            if ($result && self::isGalleryPath($absolutePath)) {
                $dateTime = date('Ymd_His', @filemtime($absolutePath) ?: time());
                $lotCounters[$dateTime] = ($lotCounters[$dateTime] ?? 0) + 1;
                $renameResult = self::renameGalleryFile(
                    $absolutePath,
                    $dateTime,
                    $lotCounters[$dateTime],
                    $quality,
                    $maxWidth,
                    $dryRun
                );
                $result = $renameResult ?? false;
            }

            if ($result) {
                $optimized++;
            } else {
                $skipped++;
            }
        }

        return [
            'directory' => $absoluteDir,
            'optimized' => $optimized,
            'skipped' => $skipped,
            'dry_run' => $dryRun,
        ];
    }

    /**
     * @param array{tmp_name:string,name:string,type:string,error:int,size:int} $file
     */
    private static function storeUploadedFile(
        array $file,
        string $destDir,
        string $filename,
        int $quality,
        ?int $maxWidth
    ): ?string {
        $tmp = $file['tmp_name'] ?? null;
        if (!$tmp || !is_file($tmp)) {
            return null;
        }

        return self::encodeToWebp($tmp, $destDir, $filename, $quality, $maxWidth);
    }

    private static function encodeToWebp(
        string $sourcePath,
        string $destDir,
        string $filename,
        int $quality,
        ?int $maxWidth
    ): ?string {
        $info = @getimagesize($sourcePath);
        $mime = $info['mime'] ?? '';

        if (!in_array($mime, self::SUPPORTED_MIMES, true)) {
            return null;
        }

        $src = self::loadImageResource($sourcePath, $mime);
        if (!$src) {
            return null;
        }

        $srcWidth = imagesx($src);
        $srcHeight = imagesy($src);
        [$targetWidth, $targetHeight] = self::computeDimensions($srcWidth, $srcHeight, $maxWidth);

        $dst = self::createCanvas($targetWidth, $targetHeight, $mime);
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $targetWidth, $targetHeight, $srcWidth, $srcHeight);

        $destPath = rtrim($destDir, '/\\') . '/' . $filename;
        $saved = @imagewebp($dst, $destPath, self::normalizeQuality($quality));

        imagedestroy($src);
        imagedestroy($dst);

        return $saved ? $destPath : null;
    }

    private static function reencodeExistingFile(
        string $absolutePath,
        int $quality,
        ?int $maxWidth,
        bool $dryRun
    ): bool {
        $info = @getimagesize($absolutePath);
        $mime = $info['mime'] ?? '';

        if (!in_array($mime, self::SUPPORTED_MIMES, true)) {
            return false;
        }

        if ($dryRun) {
            return true;
        }

        $src = self::loadImageResource($absolutePath, $mime);
        if (!$src) {
            return false;
        }

        $srcWidth = imagesx($src);
        $srcHeight = imagesy($src);
        [$targetWidth, $targetHeight] = self::computeDimensions($srcWidth, $srcHeight, $maxWidth);

        $dst = self::createCanvas($targetWidth, $targetHeight, $mime);
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $targetWidth, $targetHeight, $srcWidth, $srcHeight);

        $result = match ($mime) {
            'image/jpeg', 'image/jpg' => imagejpeg($dst, $absolutePath, self::normalizeQuality($quality)),
            'image/png' => imagepng($dst, $absolutePath, self::mapQualityToPngCompression($quality)),
            'image/webp' => imagewebp($dst, $absolutePath, self::normalizeQuality($quality)),
            'image/gif' => imagegif($dst, $absolutePath),
            default => false,
        };

        imagedestroy($src);
        imagedestroy($dst);

        return (bool) $result;
    }

    private static function loadImageResource(string $path, string $mime)
    {
        return match ($mime) {
            'image/jpeg', 'image/jpg' => @imagecreatefromjpeg($path),
            'image/png' => @imagecreatefrompng($path),
            'image/gif' => @imagecreatefromgif($path),
            'image/webp' => @imagecreatefromwebp($path),
            default => false,
        };
    }

    private static function computeDimensions(int $width, int $height, ?int $maxWidth): array
    {
        if ($maxWidth === null || $maxWidth <= 0 || $width <= $maxWidth) {
            return [$width, $height];
        }

        $ratio = $maxWidth / $width;

        return [max(1, (int) round($width * $ratio)), max(1, (int) round($height * $ratio))];
    }

    private static function createCanvas(int $width, int $height, string $mime)
    {
        $canvas = imagecreatetruecolor($width, $height);

        if (in_array($mime, ['image/png', 'image/gif', 'image/webp'], true)) {
            imagealphablending($canvas, false);
            imagesavealpha($canvas, true);
            $transparent = imagecolorallocatealpha($canvas, 255, 255, 255, 127);
            imagefilledrectangle($canvas, 0, 0, $width, $height, $transparent);
        }

        return $canvas;
    }

    private static function ensureDirectory(string $destDir): void
    {
        if (!is_dir($destDir)) {
            @mkdir($destDir, 0755, true);
        }
    }

    private static function generateSequentialWebpName(string $destDir): string
    {
        $prefix = date('Ymd');
        $existing = glob(rtrim($destDir, '/\\') . '/' . $prefix . '-*.webp');
        $num = is_array($existing) ? count($existing) + 1 : 1;

        return sprintf('%s-%d.webp', $prefix, $num);
    }

    private static function generateGalleryLotFilename(string $destDir, int $lotNumber, ?string $dateTime = null): string
    {
        $timestamp = $dateTime ?? date('Ymd_His');
        $lot = str_pad((string) max(1, $lotNumber), 3, '0', STR_PAD_LEFT);
        $filename = sprintf('%s_lot%s.webp', $timestamp, $lot);
        $fullPath = rtrim($destDir, '/\\') . '/' . $filename;

        $counter = $lotNumber;
        while (is_file($fullPath)) {
            $counter++;
            $lot = str_pad((string) $counter, 3, '0', STR_PAD_LEFT);
            $filename = sprintf('%s_lot%s.webp', $timestamp, $lot);
            $fullPath = rtrim($destDir, '/\\') . '/' . $filename;
        }

        return $filename;
    }

    private static function renameGalleryFile(
        string $absolutePath,
        string $dateTime,
        int $lotNumber,
        int $quality,
        ?int $maxWidth,
        bool $dryRun
    ): ?string {
        $dir = dirname($absolutePath);
        $newFilename = self::generateGalleryLotFilename($dir, $lotNumber, $dateTime);
        $currentBasename = basename($absolutePath);

        if ($currentBasename === $newFilename && str_ends_with(strtolower($currentBasename), '.webp')) {
            return $absolutePath;
        }

        if ($dryRun) {
            return $absolutePath;
        }

        $newPath = self::encodeToWebp($absolutePath, $dir, $newFilename, $quality, $maxWidth);
        if ($newPath && $newPath !== $absolutePath && is_file($absolutePath)) {
            @unlink($absolutePath);
        }

        return $newPath;
    }

    private static function isGalleryPath(string $path): bool
    {
        $normalized = str_replace('\\', '/', $path);

        return str_contains($normalized, '/assets/img/gallery/');
    }

    private static function normalizeQuality(int $quality): int
    {
        return max(10, min(100, $quality));
    }

    private static function mapQualityToPngCompression(int $quality): int
    {
        $quality = self::normalizeQuality($quality);
        $inverted = 100 - $quality; // 0 => meilleur, 100 => pire

        return max(0, min(9, (int) round($inverted / 10)));
    }

    private static function toPublicPath(string $absolutePath): string
    {
        $publicRoot = realpath(__DIR__ . '/../../public');
        if ($publicRoot && str_starts_with($absolutePath, $publicRoot)) {
            return str_replace($publicRoot, '', $absolutePath);
        }

        return $absolutePath;
    }
}
