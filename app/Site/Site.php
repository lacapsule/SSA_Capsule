<?php
declare(strict_types=1);

namespace App\Site;

/** Réglages du site public (config/site.php) et adresse canonique (APP_URL). */
final class Site
{
    /** @return array<string, mixed> */
    public static function config(): array
    {
        static $config = null;
        return $config ??= is_file(ROOT . '/config/site.php') ? (array) require ROOT . '/config/site.php' : [];
    }

    public static function get(string $key, mixed $default = ''): mixed
    {
        return self::config()[$key] ?? $default;
    }

    public static function url(): string
    {
        return rtrim((string) ($_ENV['APP_URL'] ?? ''), '/');
    }

    /** Adresse d'un fichier de public/ suivie de sa date de modification (pas de version périmée en cache). */
    public static function asset(string $path): string
    {
        $file = ROOT . '/public' . $path;
        return $path . (is_file($file) ? '?v=' . filemtime($file) : '');
    }

    /**
     * Pages publiques pour sitemap.xml et llms.txt : celles de modules/pages-sections-markdown si elles existent
     * (avec leur date de modification), sinon la liste « pages » de config/site.php.
     * @return list<array{path: string, title: string, description: string, updated: ?string}>
     */
    public static function pages(): array
    {
        if (class_exists(SiteController::class) && is_file(ROOT . '/config/pages.php')) {
            $updated = [];
            try {
                $updated = \App\Core\Db::run('SELECT page_key, MAX(updated_at) FROM page_contents GROUP BY page_key')->fetchAll(\PDO::FETCH_KEY_PAIR);
            } catch (\PDOException) {
                // table absente : pas de date
            }
            $out = [];
            foreach (SiteController::pages() as $path => $cfg) {
                $meta = \App\Content\Page::load($cfg['cle'])['meta'] ?? null;
                if ($meta !== null) {
                    $out[] = ['path' => (string) $path, 'title' => $meta['h1'] ?? $cfg['libelle'], 'description' => $meta['description'] ?? '', 'updated' => $updated[$cfg['cle']] ?? null];
                }
            }
            return $out;
        }
        $out = [];
        foreach ((array) self::get('pages', []) as $path => $p) {
            $out[] = ['path' => (string) $path, 'title' => (string) ($p['titre'] ?? ''), 'description' => (string) ($p['description'] ?? ''), 'updated' => null];
        }
        return $out;
    }
}
