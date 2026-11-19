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
            'meta' => ['title' => 'Page introuvable', 'description' => 'Cette page n\'existe pas ou a changé d\'adresse : retrouvez l\'accueil, le projet, l\'agenda et les lieux conventionnés de la Sécurité Sociale de l\'Alimentation du Pays de Morlaix.', 'h1' => 'Page introuvable'],
            'noindex' => true,
            // Aucune page équivalente : le sélecteur de langue mène à l'accueil de chaque langue.
            'disponibles' => [],
        ], 'site/layout');
    }
}
