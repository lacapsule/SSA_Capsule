<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Fichiers envoyés depuis l'admin : type vérifié par le contenu (pas par l'extension), nom aléatoire,
 * images ré-encodées en WebP (aucune charge utile conservée). Dossier public/media/<dossier>/.
 * Nécessite les extensions fileinfo et gd (avec WebP).
 */
final class Upload
{
    public const MAX_PDF = 20 * 1024 * 1024;
    public const MAX_IMAGE = 15 * 1024 * 1024;

    public static function hasFile(string $field): bool
    {
        return isset($_FILES[$field]) && is_array($_FILES[$field]) && ($_FILES[$field]['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
    }

    /** @return array{path: string, size: int}|string chemin public, ou message d'erreur */
    public static function pdf(string $field, string $folder = 'documents'): array|string
    {
        $file = self::file($field);
        if (is_string($file)) {
            return $file;
        }
        if ($file['size'] > self::MAX_PDF) {
            return 'Le PDF dépasse 20 Mo.';
        }
        if ((new \finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']) !== 'application/pdf') {
            return 'Le fichier n\'est pas un PDF.';
        }
        $dir = self::dir($folder);
        $name = self::slug(pathinfo($file['name'], PATHINFO_FILENAME)) . '-' . bin2hex(random_bytes(3)) . '.pdf';
        if (!move_uploaded_file($file['tmp_name'], "$dir/$name")) {
            return 'Enregistrement du fichier impossible.';
        }
        return ['path' => "/media/$folder/$name", 'size' => (int) filesize("$dir/$name")];
    }

    /**
     * Image ré-encodée en WebP. $widths : largeurs produites (jamais plus que l'original) ; $maxHeight limite la hauteur (logos).
     * @param list<int> $widths
     * @return array{path: string, width: int, height: int, variants: array<int, string>}|string
     */
    public static function image(string $field, string $folder, array $widths = [1200], ?int $maxHeight = null): array|string
    {
        $file = self::file($field);
        return is_string($file) ? $file : self::imageFichier($file, $folder, $widths, $maxHeight);
    }

    /**
     * Fichiers d'un champ à envoi multiple (name="photos[]"), un par entrée, dans l'ordre choisi ; ajout du projet SSA.
     * @return list<array{name: string, tmp_name: string, size: int}|string> fichier, ou message d'erreur de ce fichier
     */
    public static function fichiers(string $field): array
    {
        $f = $_FILES[$field] ?? null;
        if (!is_array($f) || !is_array($f['name'] ?? null)) {
            return [];
        }
        $liste = [];
        foreach (array_keys($f['name']) as $i) {
            if (($f['error'][$i] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            $liste[] = $f['error'][$i] !== UPLOAD_ERR_OK || !is_uploaded_file((string) $f['tmp_name'][$i])
                ? '« ' . (string) $f['name'][$i] . ' » : l\'envoi a échoué (taille maximale du serveur dépassée ?).'
                : ['name' => (string) $f['name'][$i], 'tmp_name' => (string) $f['tmp_name'][$i], 'size' => (int) $f['size'][$i]];
        }
        return $liste;
    }

    public const MAX_VIDEO = 64 * 1024 * 1024;

    /**
     * Vidéo MP4 ou WebM (type lu dans le contenu), gardée telle quelle : à envoyer déjà prête pour le web (format d'un
     * téléphone). @return array{path: string, size: int}|string chemin public, ou message d'erreur
     */
    public static function video(string $field, string $folder = 'videos'): array|string
    {
        $file = self::file($field);
        if (is_string($file)) {
            return $file;
        }
        if ($file['size'] > self::MAX_VIDEO) {
            return 'La vidéo dépasse 64 Mo : raccourcissez-la ou réduisez sa qualité avant de l\'envoyer.';
        }
        $type = (string) (new \finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
        $ext = ['video/mp4' => 'mp4', 'video/webm' => 'webm'][$type] ?? null;
        if ($ext === null) {
            return 'Format accepté : MP4 ou WebM.';
        }
        $dir = self::dir($folder);
        $name = self::slug(pathinfo($file['name'], PATHINFO_FILENAME)) . '-' . bin2hex(random_bytes(3)) . '.' . $ext;
        if (!move_uploaded_file($file['tmp_name'], "$dir/$name")) {
            return 'Enregistrement de la vidéo impossible.';
        }
        return ['path' => "/media/$folder/$name", 'size' => (int) filesize("$dir/$name")];
    }

    /**
     * Image ré-encodée en WebP depuis un fichier déjà lu (envoi simple ou multiple).
     * @param array{name: string, tmp_name: string, size: int} $file @param list<int> $widths
     * @return array{path: string, width: int, height: int, variants: array<int, string>}|string
     */
    public static function imageFichier(array $file, string $folder, array $widths = [1200], ?int $maxHeight = null): array|string
    {
        if ($file['size'] > self::MAX_IMAGE) {
            return 'L\'image dépasse 15 Mo.';
        }
        if (!in_array((new \finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']), ['image/jpeg', 'image/png', 'image/webp'], true)) {
            return 'Format accepté : JPG, PNG ou WebP.';
        }
        $src = @imagecreatefromstring((string) file_get_contents($file['tmp_name']));
        if ($src === false) {
            return 'Image illisible.';
        }
        $w = imagesx($src);
        $h = imagesy($src);
        $dir = self::dir($folder);
        $base = self::slug(pathinfo($file['name'], PATHINFO_FILENAME)) . '-' . bin2hex(random_bytes(3));
        $variants = [];
        foreach ($widths as $target) {
            $tw = min($target, $w);
            if ($maxHeight !== null) {
                $tw = min($tw, (int) round($w * $maxHeight / max(1, $h)));
            }
            $tw = max(1, $tw);
            $th = max(1, (int) round($h * $tw / $w));
            $dst = imagecreatetruecolor($tw, $th);
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
            imagefill($dst, 0, 0, (int) imagecolorallocatealpha($dst, 0, 0, 0, 127));
            imagecopyresampled($dst, $src, 0, 0, 0, 0, $tw, $th, $w, $h);
            imagewebp($dst, "$dir/$base-$tw.webp", 80);
            $variants[$tw] = "/media/$folder/$base-$tw.webp";
        }
        ksort($variants);
        $path = $variants[(int) array_key_last($variants)];
        [$fw, $fh] = getimagesize(ROOT . '/public' . $path) ?: [0, 0];
        return ['path' => $path, 'width' => (int) $fw, 'height' => (int) $fh, 'variants' => $variants];
    }

    /** Efface un fichier de public/media/ (jamais en dehors). */
    public static function delete(?string $path): void
    {
        if ($path !== null && preg_match('#^/media/[a-z0-9_-]+/[a-z0-9_.-]+$#i', $path) && !str_contains($path, '..')) {
            @unlink(ROOT . '/public' . $path);
        }
    }

    /** @return array{name: string, tmp_name: string, size: int}|string */
    private static function file(string $field): array|string
    {
        $f = $_FILES[$field] ?? null;
        if (!is_array($f) || ($f['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return 'Aucun fichier envoyé.';
        }
        if ($f['error'] !== UPLOAD_ERR_OK || !is_uploaded_file((string) $f['tmp_name'])) {
            return 'L\'envoi du fichier a échoué (taille maximale du serveur dépassée ?).';
        }
        return ['name' => (string) $f['name'], 'tmp_name' => (string) $f['tmp_name'], 'size' => (int) $f['size']];
    }

    private static function dir(string $folder): string
    {
        if (!preg_match('/^[a-z0-9_-]+$/', $folder)) {
            throw new \InvalidArgumentException("Dossier invalide : $folder");
        }
        $dir = ROOT . "/public/media/$folder";
        is_dir($dir) || mkdir($dir, 0775, true);
        return $dir;
    }

    private static function slug(string $value): string
    {
        $value = strtolower((string) preg_replace('/[^A-Za-z0-9]+/', '-', (string) iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value)));
        return trim(substr($value, 0, 60), '-') ?: 'fichier';
    }
}
