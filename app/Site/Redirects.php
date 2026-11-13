<?php
declare(strict_types=1);

namespace App\Site;

/**
 * Anciennes adresses vers les nouvelles, en 301 (config/redirections.tsv : ancienne<TAB>nouvelle, une par ligne,
 * « * » final pour tout un dossier, lignes « # » ignorées). La cible est toujours un chemin local : pas de redirection
 * ouverte vers un autre site.
 */
final class Redirects
{
    public static function find(string $path): ?string
    {
        $file = ROOT . '/config/redirections.tsv';
        if (!is_file($file)) {
            return null;
        }
        $normalized = rtrim($path, '/') . '/';
        foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
            if (str_starts_with($line, '#') || !str_contains($line, "\t")) {
                continue;
            }
            [$from, $to] = array_map('trim', explode("\t", $line, 2));
            $hit = str_ends_with($from, '*') ? str_starts_with($normalized, rtrim($from, '*')) : $normalized === rtrim($from, '/') . '/';
            if ($hit && preg_match('#^/(?!/)#', $to)) {
                return $to;
            }
        }
        return null;
    }

    /**
     * Une seule adresse par contenu (à appeler en tête du contrôleur frontal, pour GET et HEAD) :
     * /index.php vers /, slash final ajouté si la page existe avec, puis anciennes adresses.
     * @param callable(string): bool $exists vrai si le chemin est une page du site
     */
    public static function canonical(string $path, callable $exists): ?string
    {
        $target = match (true) {
            $path === '/index.php' => '/',
            $path === '/admin' => '/admin/',
            !str_ends_with($path, '/') && $exists($path . '/') => $path . '/',
            $exists($path) || str_starts_with($path, '/admin/') => null,
            default => self::find($path),
        };
        return $target !== null && $target !== $path ? $target : null;
    }
}
