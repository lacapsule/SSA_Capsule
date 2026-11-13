<?php
declare(strict_types=1);

namespace App\Langue;

/**
 * sitemap.xml multilingue : une entrée <url> par version linguistique, chacune avec la liste complète des versions
 * (xhtml:link hreflang, x-default compris), comme le demande Google. Une page n'est annoncée que dans les langues
 * où elle existe.
 *
 * Pages : celles de gabarits/site-public-php (Site::pages()) si le gabarit est installé, disponibles selon les
 * traductions de l'objet « page » (identifiant : chemin interne), plus les sources de config('langues.sitemap') :
 * fonctions qui renvoient des entrées {chemin, langues?, modifie?} (articles traduits, par exemple).
 */
final class Sitemap
{
    /** Route GET /sitemap.xml, déclarée après celles de site-public-php (même chemin : celle-ci remplace l'autre). */
    public static function afficher(): void
    {
        header('Content-Type: application/xml; charset=UTF-8');
        echo self::xml(self::pages());
    }

    /** @return list<array{chemin: string, langues: list<string>|null, modifie: ?string}> */
    public static function pages(): array
    {
        $pages = [];
        if (class_exists(\App\Site\Site::class)) {
            foreach (\App\Site\Site::pages() as $p) {
                $pages[] = ['chemin' => $p['path'], 'langues' => self::disponiblesPage($p['path']), 'modifie' => $p['updated']];
            }
        }
        foreach ((array) (function_exists('config') ? config('langues.sitemap', []) : []) as $source) {
            if (is_callable($source)) {
                foreach ($source() as $e) {
                    $pages[] = ['chemin' => (string) $e['chemin'], 'langues' => isset($e['langues']) ? array_values((array) $e['langues']) : null, 'modifie' => $e['modifie'] ?? null];
                }
            }
        }
        return $pages;
    }

    /** @param list<array{chemin: string, langues: list<string>|null, modifie: ?string}> $pages */
    public static function xml(array $pages): string
    {
        $x = static fn (string $v): string => htmlspecialchars($v, ENT_XML1 | ENT_QUOTES, 'UTF-8');
        $out = '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
            . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">' . "\n";
        foreach ($pages as $p) {
            $alternatives = Langue::alternatives($p['chemin'], $p['langues']);
            foreach (Langue::filtrer($p['langues']) as $code) {
                $out .= '  <url>' . "\n" . '    <loc>' . $x(Langue::absolue($p['chemin'], $code)) . '</loc>' . "\n";
                if (!empty($p['modifie'])) {
                    $out .= '    <lastmod>' . $x(substr((string) $p['modifie'], 0, 10)) . '</lastmod>' . "\n";
                }
                foreach ($alternatives as $a) {
                    $out .= '    <xhtml:link rel="alternate" hreflang="' . $x($a['hreflang']) . '" href="' . $x($a['href']) . '"/>' . "\n";
                }
                $out .= '  </url>' . "\n";
            }
        }
        return $out . '</urlset>' . "\n";
    }

    /** @return list<string>|null */
    private static function disponiblesPage(string $chemin): ?array
    {
        try {
            return Contenu::disponibles('page', $chemin);
        } catch (\PDOException) {
            return [Langue::defaut()];
        }
    }
}
