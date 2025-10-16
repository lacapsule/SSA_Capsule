<?php

declare(strict_types=1);

namespace App\Modules\Article;

use App\Modules\Article\Dto\ArticleDTO;
use Capsule\View\Presenter\IterablePresenter;

/**
 * Projection “dashboard/articles” → données prêtes pour MiniMustache.
 * Règle: on matérialise (array) juste avant la vue (page-size), car la vue peut ré-itérer.
 */
final class ArticlePresenter
{
    /**
     * Liste (index)
     * @param array<string,mixed> $base   // shell dashboard déjà prêt (str, user, links, flash…)
     * @param iterable<ArticleDTO> $articles
     * @return array<string,mixed>
     */
    public static function list(array $base, iterable $articles, int $page, int $limit, string $csrfInput): array
    {
        $mapped = IterablePresenter::map($articles, function (ArticleDTO $a): array {
            $id = (int)$a->id;

            // Date courte pour tableau (fallback “as is” si invalide)
            $dateStr = (string)($a->date_article ?? '');
            if ($dateStr !== '') {
                try {
                    $dateStr = (new \DateTime($dateStr))->format('d/m/Y');
                } catch (\Throwable) { /* keep raw */
                }
            }

            // URLs d’action
            $editBase = '/dashboard/articles/edit';
            $deleteBase = '/dashboard/articles/delete';
            $showBase = '/dashboard/articles/show';

            return [
                'id' => $id,
                'titre' => (string)$a->titre,
                'resume' => (string)$a->resume,
                'date' => $dateStr,
                'author' => (string)($a->author ?? 'Inconnu'),
                'editUrl' => rtrim($editBase, '/') . '/' . rawurlencode((string)$id),
                'deleteUrl' => rtrim($deleteBase, '/') . '/' . rawurlencode((string)$id),
                'showUrl' => rtrim($showBase, '/') . '/' . rawurlencode((string)$id),
            ];
        });

        // FRONTIÈRE VUE : matérialise uniquement la page courante
        $items = IterablePresenter::toArray($mapped, $limit); // supporte un $limit optionnel

        return $base + [
            'title' => 'Articles',
            'component' => 'dashboard/dash_articles',
            'createUrl' => '/dashboard/articles/create',
            'articles' => $items,       // array ré-itérable
            'csrf_input' => $csrfInput,
            'pagination' => ['page' => $page, 'limit' => $limit],
        ];
    }

    /** Détail */
    public static function show(array $base, ArticleDTO $a): array
    {
        return $base + [
            'title' => 'Détail de l’article',
            'component' => 'dashboard/dash_article_show',
            'article' => [
                'title' => (string)$a->titre,
                'summary' => (string)$a->resume,
                'description' => (string)($a->description ?? ''),
                'date' => (string)$a->date_article,
                'time' => substr((string)$a->hours, 0, 5),
                'location' => (string)($a->lieu ?? ''),
                'author' => (string)($a->author ?? 'Inconnu'),
                'backUrl' => '/dashboard/articles',
            ],
        ];
    }

    /**
     * Formulaire (création/édition) – on passe un tableau simple à la vue.
     * @param array<string,mixed>|ArticleDTO|null $src
     * @param array<string,string> $errors
     * @return array<string,mixed>
     */
    public static function form(array $base, string $title, string $action, array|ArticleDTO|null $src, array $errors, string $csrfInput): array
    {
        $data = self::toFormData($src);

        return $base + [
            'title' => $title,
            'component' => 'dashboard/dash_article_form',
            'action' => $action,
            'article' => $data,
            'errors' => $errors,
            'csrf_input' => $csrfInput,
        ];
    }

    /** @return array<string,string> */
    private static function toFormData(array|ArticleDTO|null $src): array
    {
        if ($src === null) {
            return [
                'titre' => '',
                'resume' => '',
                'description' => '',
                'date_article' => '',
                'hours' => '',
                'lieu' => '',
            ];
        }
        if ($src instanceof ArticleDTO) {
            return [
                'titre' => (string)$src->titre,
                'resume' => (string)$src->resume,
                'description' => (string)($src->description ?? ''),
                'date_article' => (string)$src->date_article,
                'hours' => (string)$src->hours,
                'lieu' => (string)($src->lieu ?? ''),
            ];
        }

        // array POST (prefill)
        return [
            'titre' => (string)($src['titre'] ?? ''),
            'resume' => (string)($src['resume'] ?? ''),
            'description' => (string)($src['description'] ?? ''),
            'date_article' => (string)($src['date_article'] ?? ''),
            'hours' => (string)($src['hours'] ?? ''),
            'lieu' => (string)($src['lieu'] ?? ''),
        ];
    }
}
