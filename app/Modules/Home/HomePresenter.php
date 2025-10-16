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
    /**
     * @return array{
     *   articles: iterable<array{
     *     id:int,date:string,time:string,title:string,summary:string,
     *     location:string,ics_datetime:string,titre:string,resume:string,
     *     image:string,category:string
     *   }>,
     *   partenaires: array<array{name:string,role:string,url:string,logo:string}>,
     *   financeurs: array<array{name:string,role:string,url:string,logo:string}>
     * }
     */
    public static function forView(HomeDTO $dto): array
    {
        $articlesIt = IterablePresenter::map($dto->articles, function ($a) {
            $date = (string)($a->date_article ?? '');
            $time = substr((string)($a->hours ?? ''), 0, 5);
            $title = (string)($a->titre ?? '');
            $sum = (string)($a->resume ?? '');

            return [
                // — clés Agenda (existantes) —
                'date' => $date,
                'time' => $time,
                'title' => $title,
                'summary' => $sum,
                'location' => (string)($a->lieu ?? ''),
                'ics_datetime' => $date . ' ' . $time . ':00',

                // — clés Actualités (attendues par actualites.tpl.php) —
                'id' => (int)($a->id ?? 0),
                'titre' => $title,
                'resume' => $sum,
                'image' => Safe::imageUrl((string)($a->image ?? '/assets/img/placeholder.webp')),
                'category' => (string)($a->category ?? 'general'),
            ];
        });

        $articles = IterablePresenter::toArray($articlesIt);

        return [
          'articles' => $articles,  // array ré-itérable
          'partenaires' => $dto->partenaires,
          'financeurs' => $dto->financeurs,
        ];
    }
}
