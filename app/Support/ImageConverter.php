<?php

declare(strict_types=1);

namespace App\Support;

use FilesystemIterator;
use finfo;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

final class ImageConverter
{
    private const SUPPORTED_MIMES = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    private const DEFAULT_QUALITY = 80;
    private const DEFAULT_MAX_WIDTH = 1920;
    private const SUPPORTED_VIDEO_MIMES = [
        'video/mp4' => 'mp4',
        'video/webm' => 'webm',
        'video/ogg' => 'ogv',
        'video/quicktime' => 'mov',
    ];

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
     * Convertit un fichier uploadé en WebP pour un article (même traitement que la galerie)
     * et retourne le chemin public complet.
     *
     * @param array{tmp_name:string,name:string,type:string,error:int,size:int} $file
     */
    public static function convertUploadedFileForArticle(
        array $file,
        int $articleId,
        ?string $destDirAbsolute = null,
        int $quality = self::DEFAULT_QUALITY,
        ?int $maxWidth = self::DEFAULT_MAX_WIDTH
    ): ?string {
        $destDir = self::resolveArticleDirectory($articleId, $destDirAbsolute);
        self::ensureDirectory($destDir);

        $filename = self::generateTimestampedName($destDir, 'webp');
        $savedPath = self::storeUploadedFile($file, $destDir, $filename, $quality, $maxWidth);

        return $savedPath ? self::toPublicPath($savedPath) : null;
    }

    /**
     * Sauvegarde un média (image ou vidéo) pour un article.
     *
     * @param array{tmp_name:string,name:string,type:string,error:int,size:int} $file
     */
    public static function saveArticleMedia(array $file, int $articleId, ?string $destDirAbsolute = null): ?string
    {
        $mime = self::detectMimeType($file);

        if (isset(self::SUPPORTED_VIDEO_MIMES[$mime])) {
            return self::moveUploadedVideoForArticle($file, $articleId, $destDirAbsolute);
        }

        return self::convertUploadedFileForArticle($file, $articleId, $destDirAbsolute);
    }

    /**
     * Déplace un fichier vidéo (mp4/webm/...) vers le dossier de l'article.
     *
     * @param array{tmp_name:string,name:string,type:string,error:int,size:int} $file
     */
    public static function moveUploadedVideoForArticle(
        array $file,
        int $articleId,
        ?string $destDirAbsolute = null
    ): ?string {
        $tmp = $file['tmp_name'] ?? null;
        if (!$tmp || !is_file($tmp)) {
            return null;
        }

        $mime = self::detectMimeType($file);
        $extension = self::SUPPORTED_VIDEO_MIMES[$mime] ?? null;
        if ($extension === null) {
            return null;
        }

        $destDir = self::resolveArticleDirectory($articleId, $destDirAbsolute);
        self::ensureDirectory($destDir);

        $filename = self::generateTimestampedName($destDir, $extension);
        $destPath = rtrim($destDir, '/\\') . '/' . $filename;

        $moved = @move_uploaded_file($tmp, $destPath);
        if (!$moved) {
            $moved = @copy($tmp, $destPath);
        }

        return $moved ? self::toPublicPath($destPath) : null;
    }

    /**
     * Convertit un fichier uploadé en WebP pour la galerie avec un nom personnalisé
     * et retourne uniquement le filename (ex: mon-image.webp).
     *
     * @param array{tmp_name:string,name:string,type:string,error:int,size:int} $file
     */
    public static function convertUploadedFileForGallery(
        array $file,
        ?string $customName = null,
        ?string $destDirAbsolute = null,
        int $quality = self::DEFAULT_QUALITY,
        ?int $maxWidth = self::DEFAULT_MAX_WIDTH
    ): ?string {
        $basePath = realpath(__DIR__ . '/../../') ?: dirname(__DIR__, 2);
        $destDir = $destDirAbsolute ?? ($basePath . '/public/assets/img/gallery');
        self::ensureDirectory($destDir);

        $filename = $customName !== null && $customName !== ''
            ? self::sanitizeCustomName($customName) . '.webp'
            : self::generateTimestampedWebpName($destDir);

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
        foreach ($iterator as $file) {
            if (!$file->isFile()) {
                continue;
            }

            $ext = strtolower($file->getExtension());
            if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) {
                continue;
            }

            $result = self::reencodeExistingFile($file->getPathname(), $quality, $maxWidth, $dryRun);

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

    private static function generateTimestampedWebpName(string $destDir): string
    {
        return self::generateTimestampedName($destDir, 'webp');
    }

    private static function sanitizeCustomName(string $customName): string
    {
        $cleanName = preg_replace('/[^a-zA-Z0-9_-]/', '-', $customName);
        $cleanName = preg_replace('/-+/', '-', $cleanName ?? '');
        $cleanName = trim((string) $cleanName, '-');

        return $cleanName !== '' ? $cleanName : 'image';
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

    private static function detectMimeType(array $file): string
    {
        $tmp = $file['tmp_name'] ?? null;
        if ($tmp && is_file($tmp)) {
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($tmp);
            if (is_string($mime) && $mime !== '') {
                return $mime;
            }
        }

        return isset($file['type']) ? (string) $file['type'] : '';
    }

    private static function resolveArticleDirectory(int $articleId, ?string $destDirAbsolute): string
    {
        if ($destDirAbsolute !== null) {
            return rtrim($destDirAbsolute, '/\\');
        }

        $basePath = realpath(__DIR__ . '/../../') ?: dirname(__DIR__, 2);

        return rtrim($basePath . '/public/assets/img/articles/' . $articleId, '/\\');
    }

    private static function generateTimestampedName(string $destDir, string $extension): string
    {
        $extension = ltrim($extension, '.');
        $prefix = date('Ymd-His');
        $index = 1;
        $normalizedDir = rtrim($destDir, '/\\') . '/';

        do {
            $filename = sprintf('%s-%d.%s', $prefix, $index, strtolower($extension));
            $index++;
        } while (is_file($normalizedDir . $filename));

        return $filename;
    }
}
