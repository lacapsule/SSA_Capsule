<?php

declare(strict_types=1);

namespace App\Modules\Home;

use App\Modules\Article\ArticleService;
use App\Modules\Home\Dto\HomeDTO;
use App\Providers\PartnersProvider;
use Capsule\Support\Pagination\Page;

/**
 * HomeService
 * - Agrège les sources nécessaires à la page d'accueil.
 * - Ne fait AUCUN rendu, AUCUNE I/O de vue, pas d'accès session/cookies.
 *
 * Contrats & invariants :
 * - Entrée : Page (page/limit/offet) déjà validée par le contrôleur.
 * - Sortie : HomeDTO (articles iterable<object>, partenaires/financeurs arrays).
 * - Aucune dépendance sur MiniMustache / i18n / CSRF.
 */
final class HomeService
{
    public function __construct(
        private ArticleService $articles,
        private PartnersProvider $partners
    ) {
    }

    public function getHomeData(Page $page): HomeDTO
    {
        // Domaine : liste paginée (lazy si le repo le permet)
        $rows = $this->articles->getUpcomingPage($page->limit, $page->offset());

        // Config : partenaires/financeurs (statiques, preload/OPcache-friendly)
        $partenaires = $this->partners->byRole('partenaire');
        $financeurs = $this->partners->byRole('financeur');

        return new HomeDTO($rows, $partenaires, $financeurs);
    }
}
