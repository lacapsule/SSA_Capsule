<?php
declare(strict_types=1);

namespace App\Site;

/**
 * Copies réduites des photos (rule web-perf-eco : image à la dimension affichée, page < 500 Ko).
 * Une copie se crée au premier affichage dans public/media/vignettes/<largeur>/ (dossier non versionné, comme public/media),
 * puis se recrée si l'original change. L'original n'est jamais modifié. Sans GD, ou si l'original est déjà assez petit,
 * l'original est servi tel quel.
 */
final class Vignette
{
    /** Largeurs produites : une liste courte, pour que les copies servent d'une page à l'autre. */
    public const LARGEURS = [400, 640, 960, 1280];

    private const DOSSIER = '/media/vignettes';

    /** Chemin public de la copie de $chemin à $largeur pixels de large au plus ; l'original en cas d'échec. */
    public static function chemin(string $chemin, int $largeur): string
    {
        $source = self::source($chemin);
        if ($source === null || !in_array($largeur, self::LARGEURS, true)) {
            return $chemin;
        }
        $taille = getimagesize($source) ?: null;
        if ($taille === null || $taille[0] <= $largeur) {
            return $chemin;
        }
        // Chemin complet de l'original dans le nom de la copie : /media/a.webp et /assets/a.webp ne se confondent pas.
        $relatif = preg_replace('/\.[a-z0-9]+$/i', '', ltrim($chemin, '/')) . '.webp';
        $public = self::DOSSIER . '/' . $largeur . '/' . $relatif;
        $cible = ROOT . '/public' . $public;
        if (is_file($cible) && filemtime($cible) >= filemtime($source)) {
            return $public;
        }
        return self::reduire($source, $cible, $largeur, $taille) ? $public : $chemin;
    }

    /**
     * Attributs src, srcset et sizes d'une image affichée à $sizes (ex. « (max-width: 48rem) 100vw, 40rem »),
     * $src étant la copie de $largeurSrc pixels (repli des navigateurs sans srcset). Prêts à insérer dans la balise.
     */
    public static function attributs(string $chemin, string $sizes, int $largeurSrc = 640): string
    {
        $source = self::source($chemin);
        $max = $source !== null ? ((getimagesize($source) ?: [0])[0]) : 0;
        $candidats = [];
        foreach (self::LARGEURS as $l) {
            if ($l < $max) {
                $candidats[] = e(self::chemin($chemin, $l)) . ' ' . $l . 'w';
            }
        }
        if ($max > 0) {
            $candidats[] = e($chemin) . ' ' . $max . 'w';
        }
        // Photo plus petite que la largeur annoncée : la largeur annoncée devient la sienne, sinon le navigateur l'agrandit
        // (avec srcset, la taille affichée suit sizes) et elle se pixelise.
        if ($max > 0 && preg_match('/^(.*?)(\d+(?:\.\d+)?)rem$/', $sizes, $m) === 1 && $max < (float) $m[2] * 16) {
            $sizes = $m[1] . $max . 'px';
        }
        $src = e(self::chemin($chemin, $largeurSrc));
        return 'src="' . $src . '"' . (count($candidats) > 1 ? ' srcset="' . implode(', ', $candidats) . '" sizes="' . e($sizes) . '"' : '');
    }

    /** Largeur et hauteur réelles de l'image d'origine, ou null. @return ?array{0: int, 1: int} */
    public static function taille(string $chemin): ?array
    {
        $source = self::source($chemin);
        $t = $source !== null ? getimagesize($source) : false;
        return $t !== false ? [(int) $t[0], (int) $t[1]] : null;
    }

    /** Fichier d'origine sous public/, ou null : chemin local sans « .. », image existante. */
    private static function source(string $chemin): ?string
    {
        if (!preg_match('#^/(media|assets)/[A-Za-z0-9/_.-]+\.(webp|jpe?g|png)$#i', $chemin) || str_contains($chemin, '..') || str_starts_with($chemin, self::DOSSIER . '/')) {
            return null;
        }
        $fichier = realpath(ROOT . '/public' . $chemin);
        $racine = realpath(ROOT . '/public');
        return $fichier !== false && $racine !== false && str_starts_with($fichier, $racine . '/') ? $fichier : null;
    }

    /** @param array{0: int, 1: int, 2: int} $taille */
    private static function reduire(string $source, string $cible, int $largeur, array $taille): bool
    {
        if (!function_exists('imagewebp')) {
            return false;
        }
        $image = match ($taille[2]) {
            IMAGETYPE_WEBP => imagecreatefromwebp($source),
            IMAGETYPE_JPEG => imagecreatefromjpeg($source),
            IMAGETYPE_PNG => imagecreatefrompng($source),
            default => false,
        };
        if ($image === false) {
            return false;
        }
        $reduite = imagescale($image, $largeur, (int) round($taille[1] * $largeur / $taille[0]), IMG_BICUBIC);
        imagedestroy($image);
        if ($reduite === false) {
            return false;
        }
        imagealphablending($reduite, false);
        imagesavealpha($reduite, true);
        $dossier = dirname($cible);
        if (!is_dir($dossier) && !mkdir($dossier, 0775, true) && !is_dir($dossier)) {
            imagedestroy($reduite);
            return false;
        }
        // Écriture dans un fichier temporaire puis renommage : deux visites simultanées ne servent jamais une copie à moitié écrite.
        $temporaire = $cible . '.' . bin2hex(random_bytes(4)) . '.tmp';
        $ok = imagewebp($reduite, $temporaire, 72) && rename($temporaire, $cible);
        imagedestroy($reduite);
        if (!$ok && is_file($temporaire)) {
            unlink($temporaire);
        }
        return $ok;
    }
}
