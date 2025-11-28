<?php

declare(strict_types=1);

namespace App\Modules\Home;

use App\Modules\Article\ArticleService;
use Capsule\Contracts\ResponseFactoryInterface;
use Capsule\Routing\Attribute\Route;
use Capsule\View\BaseController;

final class SitemapController extends BaseController
{
    private const BASE_URL = 'https://ssapaysdemorlaix.fr';
    private const CHANGE_FREQ = 'weekly';
    private const PRIORITY_HOME = '1.0';
    private const PRIORITY_PAGES = '0.8';
    private const PRIORITY_ARTICLES = '0.7';

    public function __construct(
        private ArticleService $articleService,
        ResponseFactoryInterface $res
    ) {
        parent::__construct($res, $this->view ?? null);
    }

    /**
     * GET /sitemap.xml
     * Génère le sitemap XML pour les moteurs de recherche
     */
    #[Route(path: '/sitemap.xml', methods: ['GET'])]
    public function sitemap(): \Capsule\Http\Message\Response
    {
        $urls = [];

        // Page d'accueil
        $urls[] = [
            'loc' => self::BASE_URL . '/',
            'lastmod' => date('Y-m-d'),
            'changefreq' => 'daily',
            'priority' => self::PRIORITY_HOME,
        ];

        // Page projet
        $urls[] = [
            'loc' => self::BASE_URL . '/projet',
            'lastmod' => date('Y-m-d'),
            'changefreq' => self::CHANGE_FREQ,
            'priority' => self::PRIORITY_PAGES,
        ];

        // Page galerie
        $urls[] = [
            'loc' => self::BASE_URL . '/galerie',
            'lastmod' => date('Y-m-d'),
            'changefreq' => 'daily',
            'priority' => self::PRIORITY_PAGES,
        ];

        // Articles
        $articles = $this->articleService->getAll();
        foreach ($articles as $article) {
            // Utiliser created_at pour lastmod, ou date_article si created_at n'est pas disponible
            $lastmod = !empty($article->created_at) 
                ? (new \DateTime($article->created_at))->format('Y-m-d')
                : (!empty($article->date_article) ? $article->date_article : date('Y-m-d'));
            
            $urls[] = [
                'loc' => self::BASE_URL . '/article/' . $article->id,
                'lastmod' => $lastmod,
                'changefreq' => 'monthly',
                'priority' => self::PRIORITY_ARTICLES,
            ];
        }

        // Génération du XML
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        foreach ($urls as $url) {
            $xml .= '  <url>' . "\n";
            $xml .= '    <loc>' . htmlspecialchars($url['loc'], ENT_XML1, 'UTF-8') . '</loc>' . "\n";
            $xml .= '    <lastmod>' . htmlspecialchars($url['lastmod'], ENT_XML1, 'UTF-8') . '</lastmod>' . "\n";
            $xml .= '    <changefreq>' . htmlspecialchars($url['changefreq'], ENT_XML1, 'UTF-8') . '</changefreq>' . "\n";
            $xml .= '    <priority>' . htmlspecialchars($url['priority'], ENT_XML1, 'UTF-8') . '</priority>' . "\n";
            $xml .= '  </url>' . "\n";
        }

        $xml .= '</urlset>';

        $response = $this->res->html($xml);
        return $response->withHeader('Content-Type', 'application/xml; charset=UTF-8');
    }
}

