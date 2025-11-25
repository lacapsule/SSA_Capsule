<?php

declare(strict_types=1);

namespace App\Modules\Home;

use App\Modules\Home\Dto\HomeDTO;
use Capsule\View\Safe;
use Capsule\View\Presenter\IterablePresenter;

/**
 * HomePresenter
 * - Projection DOMAINE -> VUE.
 * - Transforme HomeDTO en structures prêtes pour MiniMustache.
 *
 * Invariants :
 * - Aucune I/O, pas de session/$_GET.
 * - Les champs utilisés en triple moustache {{{ }}} doivent passer par Safe::*.
 */
final class HomePresenter
{
    private static ?\IntlDateFormatter $dateFormatter = null;
    private static function buildPagination(\Capsule\Support\Pagination\Page $page): array
    {
        $totalPages = $page->pages();
        $currentPage = max(1, min($totalPages, $page->page));

        $pages = [];
        for ($i = 1; $i <= $totalPages; $i++) {
            $pages[] = [
                'number' => $i,
                'isCurrent' => $i === $currentPage,
            ];
        }

        return [
            'current' => $currentPage,
            'total' => $totalPages,
            'hasPrev' => $page->hasPrev(),
            'hasNext' => $page->hasNext(),
            'prev' => max(1, $currentPage - 1),
            'next' => min($totalPages, $currentPage + 1),
            'first' => 1,
            'last' => $totalPages,
            'hasFirst' => $currentPage > 1,
            'hasLast' => $currentPage < $totalPages,
            'pages' => $pages,
            'showPagination' => $totalPages >= 1,
        ];
    }

    /**
     * Obtient une instance réutilisable du formateur de date.
     * Pattern singleton pour éviter les instanciations multiples.
     */
    private static function getDateFormatter(): \IntlDateFormatter
    {
        if (self::$dateFormatter === null) {
            self::$dateFormatter = new \IntlDateFormatter(
                'fr_FR',
                \IntlDateFormatter::FULL,
                \IntlDateFormatter::NONE,
                'Europe/Paris',
                \IntlDateFormatter::GREGORIAN,
                'EEEE d MMMM yyyy'
            );
            self::$dateFormatter->setLenient(false);
        }

        return self::$dateFormatter;
    }

    /**
     * Formate une date au format français "Jeudi 10 août 2025".
     * Retourne la chaîne originale si le format est invalide.
     */
    private static function formatDateFr(string $date): string
    {
        if ($date === '') {
            return '';
        }

        $dateObj = \DateTime::createFromFormat('Y-m-d', $date);
        if ($dateObj === false || $dateObj->format('Y-m-d') !== $date) {
            return $date; // Retourne la date brute si invalide
        }

        return self::getDateFormatter()->format($dateObj) ?: $date;
    }

    /**
     * @return array{
     *   articles: array<array{
     *     id:int,date:string,time:string,title:string,summary:string,
     *     location:string,ics_datetime:string,titre:string,resume:string,
     *     image:string,category:string,date_actu:string,date_event:string
     *   }>,
     *   partenaires: array<array{name:string,role:string,url:string,logo:string}>,
     *   financeurs: array<array{name:string,role:string,url:string,logo:string}>
     * }
     */
    public static function forView(HomeDTO $dto): array
    {
        $articlesIt = IterablePresenter::map($dto->articles, static function ($a) {
            $date = (string)($a->date_article ?? '');
            $time = substr((string)($a->hours ?? ''), 0, 5);
            $title = (string)($a->titre ?? '');
            $sum = (string)($a->resume ?? '');

            $formattedDate = self::formatDateFr($date);

            return [
                // — clés Agenda —
                'date' => $formattedDate,
                'date_actu' => $date,
                'date_event' => $formattedDate,
                'time' => $time,
                'title' => $title,
                'summary' => $sum,
                'location' => (string)($a->lieu ?? ''),
                'ics_datetime' => $date . ' ' . $time . ':00',

                // — clés Actualités —
                'id' => (int)($a->id ?? 0),
                'titre' => $title,
                'resume' => $sum,
                'image' => Safe::imageUrl((string)($a->image ?? '/assets/img/logoSSA.png')),
                'category' => (string)($a->category ?? 'general'),
            ];
        });

        return [
          'articles' => IterablePresenter::toArray($articlesIt),
          'partenaires' => $dto->partenaires,
          'financeurs' => $dto->financeurs,
          'pagination' => self::buildPagination($dto->page),
        ];
    }
}
