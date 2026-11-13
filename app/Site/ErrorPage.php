<?php
declare(strict_types=1);

namespace App\Site;

use App\Core\View;

/** Page 404 du site public, dans la mise en page du site (non indexée). */
final class ErrorPage
{
    public static function notFound(): void
    {
        http_response_code(404);
        View::render('site/404', [
            'path' => '/404',
            'meta' => ['title' => 'Page introuvable', 'description' => '', 'h1' => 'Page introuvable'],
            'noindex' => true,
        ], 'site/layout');
    }
}
