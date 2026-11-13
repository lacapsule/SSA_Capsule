<?php
declare(strict_types=1);

namespace App\Site;

/** robots.txt, sitemap.xml et llms.txt, générés depuis la même source que les pages (jamais de fichier à tenir à jour). */
final class Discovery
{
    public static function robots(): void
    {
        header('Content-Type: text/plain; charset=UTF-8');
        // Hors production (APP_ENV différent de « production ») : rien n'est indexé.
        if (($_ENV['APP_ENV'] ?? 'production') !== 'production') {
            echo "User-agent: *\nDisallow: /\n";
            return;
        }
        echo "User-agent: *\nDisallow: /admin/\nDisallow: /newsletter/\nAllow: /\n\nSitemap: " . Site::url() . "/sitemap.xml\n";
    }

    public static function sitemap(): void
    {
        header('Content-Type: application/xml; charset=UTF-8');
        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        foreach (Site::pages() as $p) {
            echo '  <url><loc>' . htmlspecialchars(Site::url() . $p['path'], ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</loc>'
                . ($p['updated'] !== null ? '<lastmod>' . substr($p['updated'], 0, 10) . '</lastmod>' : '') . "</url>\n";
        }
        echo "</urlset>\n";
    }

    /** Résumé lisible par les assistants (format llms.txt) : nom, description, pages. */
    public static function llms(): void
    {
        header('Content-Type: text/markdown; charset=UTF-8');
        $out = '# ' . Site::get('nom') . "\n\n> " . Site::get('description') . "\n";
        if (Site::get('email') !== '') {
            $out .= "\nContact : " . Site::get('email') . "\n";
        }
        $out .= "\n## Pages\n\n";
        foreach (Site::pages() as $p) {
            $out .= '- [' . $p['title'] . '](' . Site::url() . $p['path'] . ')' . ($p['description'] !== '' ? ' : ' . $p['description'] : '') . "\n";
        }
        echo $out;
    }
}
