<?php

namespace App\Modules\Seo;

use App\Modules\Article\ArticleService;
use Capsule\Contracts\ResponseFactoryInterface;
use Capsule\Http\Message\Response;
use Capsule\Routing\Attribute\Route;

final class SeoController
{
    /** URL canonique de production (le sitemap doit pointer la prod, pas le dev). */
    private const BASE = 'https://ssapaysdemorlaix.fr';

    public function __construct(
        private ResponseFactoryInterface $res,
        private ArticleService $articles,
    ) {
    }

    #[Route(path: '/robots.txt', methods: ['GET'])]
    public function robots(): Response
    {
        $body = "User-agent: *\n"
            . "Disallow: /dashboard\n"
            . "Disallow: /login\n"
            . "Allow: /\n\n"
            . 'Sitemap: ' . self::BASE . "/sitemap.xml\n";

        return $this->res->text($body, 200)
            ->withHeader('Content-Type', 'text/plain; charset=UTF-8');
    }

    #[Route(path: '/sitemap.xml', methods: ['GET'])]
    public function sitemap(): Response
    {
        $pages = [
            ['/', '1.0'],
            ['/projet', '0.8'],
            ['/galerie', '0.6'],
            ['/mentions-legales', '0.2'],
            ['/confidentialite', '0.2'],
            ['/accessibilite', '0.2'],
        ];

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
            . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        foreach ($pages as [$loc, $priority]) {
            $xml .= '  <url><loc>' . self::BASE . $this->esc($loc) . '</loc>'
                . '<priority>' . $priority . '</priority></url>' . "\n";
        }

        foreach ($this->articles->getAll() as $article) {
            $id = is_array($article) ? ($article['id'] ?? null) : ($article->id ?? null);
            if ($id === null) {
                continue;
            }
            $lastmod = is_array($article) ? ($article['date_article'] ?? null) : ($article->date_article ?? null);
            $xml .= '  <url><loc>' . self::BASE . '/article/' . (int) $id . '</loc>';
            if ($lastmod) {
                $xml .= '<lastmod>' . $this->esc((string) $lastmod) . '</lastmod>';
            }
            $xml .= '</url>' . "\n";
        }

        $xml .= '</urlset>' . "\n";

        return $this->res->text($xml, 200)
            ->withHeader('Content-Type', 'application/xml; charset=UTF-8');
    }

    private function esc(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
