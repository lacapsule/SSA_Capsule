<?php

declare(strict_types=1);

namespace App\Support;

use Capsule\Support\Pagination\Page;

final class PaginationRenderer
{
    /**
     * @param array{
     *   base_path?: string,
     *   extra_query?: array<string, scalar|null>,
     *   page_param?: string,
     *   anchor?: string|null,
     *   max_links?: int,
     *   always_show?: bool
     * } $options
     *
     * @return array{
     *   show: bool,
     *   current: int,
     *   total: int,
     *   perPage: int,
     *   hasPrev: bool,
     *   hasNext: bool,
     *   prev: int,
     *   next: int,
     *   prevUrl: ?string,
     *   nextUrl: ?string,
     *   firstUrl: string,
     *   lastUrl: string,
     *   pages: list<array{number:int,url:string,isCurrent:bool}>,
     *   showFirstEdge: bool,
     *   showLastEdge: bool,
     *   showGapBefore: bool,
     *   showGapAfter: bool
     * }
     */
    public static function build(Page $page, array $options = []): array
    {
        $basePath = (string)($options['base_path'] ?? '');
        $extraQuery = self::sanitizeQuery($options['extra_query'] ?? []);
        $pageParam = (string)($options['page_param'] ?? 'page');
        $anchor = self::normalizeAnchor($options['anchor'] ?? null);
        $maxLinks = max(3, (int)($options['max_links'] ?? 7));
        $alwaysShow = (bool)($options['always_show'] ?? true);

        unset($extraQuery[$pageParam]);

        $totalPages = max(1, $page->pages());
        $current = max(1, min($page->page, $totalPages));

        [$start, $end] = self::computeWindow($current, $totalPages, $maxLinks);

        $pages = [];
        for ($i = $start; $i <= $end; $i++) {
            $pages[] = [
                'number' => $i,
                'url' => self::buildUrl($basePath, $extraQuery, $pageParam, $i, $anchor),
                'isCurrent' => $i === $current,
            ];
        }

        $show = $alwaysShow || $totalPages > 1;
        $prevPage = max(1, $current - 1);
        $nextPage = min($totalPages, $current + 1);

        return [
            'show' => $show,
            'current' => $current,
            'total' => $totalPages,
            'perPage' => $page->limit,
            'hasPrev' => $current > 1,
            'hasNext' => $current < $totalPages,
            'prev' => $prevPage,
            'next' => $nextPage,
            'prevUrl' => $show && $current > 1 ? self::buildUrl($basePath, $extraQuery, $pageParam, $prevPage, $anchor) : null,
            'nextUrl' => $show && $current < $totalPages ? self::buildUrl($basePath, $extraQuery, $pageParam, $nextPage, $anchor) : null,
            'firstUrl' => self::buildUrl($basePath, $extraQuery, $pageParam, 1, $anchor),
            'lastUrl' => self::buildUrl($basePath, $extraQuery, $pageParam, $totalPages, $anchor),
            'pages' => $pages,
            'showFirstEdge' => $start > 1,
            'showLastEdge' => $end < $totalPages,
            'showGapBefore' => $start > 2,
            'showGapAfter' => $end < ($totalPages - 1),
        ];
    }

    /**
     * @param array<string, mixed> $query
     * @return array<string, string>
     */
    private static function sanitizeQuery(array $query): array
    {
        $clean = [];
        foreach ($query as $key => $value) {
            if ($value === null) {
                continue;
            }
            $clean[(string)$key] = (string)$value;
        }

        return $clean;
    }

    private static function normalizeAnchor(?string $anchor): string
    {
        if ($anchor === null || $anchor === '') {
            return '';
        }

        $trimmed = ltrim($anchor, '#');

        return $trimmed === '' ? '' : '#' . $trimmed;
    }

    private static function buildUrl(
        string $basePath,
        array $query,
        string $pageParam,
        int $pageValue,
        string $anchor
    ): string {
        $query[$pageParam] = $pageValue;
        $qs = http_build_query($query);

        if ($basePath === '' || $basePath === null) {
            $url = $qs === '' ? '' : '?' . $qs;
        } else {
            $separator = str_contains($basePath, '?') ? '&' : '?';
            $url = $basePath;
            if ($qs !== '') {
                $url .= $separator . $qs;
            }
        }

        return $url . $anchor;
    }

    /**
     * @return array{0:int,1:int}
     */
    private static function computeWindow(int $current, int $totalPages, int $maxLinks): array
    {
        if ($totalPages <= $maxLinks) {
            return [1, $totalPages];
        }

        $half = intdiv($maxLinks, 2);
        $start = max(1, $current - $half);
        $end = $start + $maxLinks - 1;

        if ($end > $totalPages) {
            $end = $totalPages;
            $start = $end - $maxLinks + 1;
        }

        return [$start, $end];
    }
}

