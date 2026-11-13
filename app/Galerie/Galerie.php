<?php
declare(strict_types=1);

namespace App\Galerie;

use App\Core\Db;
use App\Core\View;
use App\Langue\Contenu;
use App\Pages\Pages;

/** Galerie (maquette docs/maquettes/galerie.md) : photos de la table galerie_photos, 24 par page, visionneuse. */
final class Galerie
{
    private const PAR_PAGE = 24;

    public static function liste(): void
    {
        $total = (int) Db::run('SELECT COUNT(*) FROM galerie_photos')->fetchColumn();
        $pages = max(1, (int) ceil($total / self::PAR_PAGE));
        $page = filter_var($_GET['page'] ?? 1, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: 1;
        if ($page > $pages) {
            abort(404);
        }
        $debut = ($page - 1) * self::PAR_PAGE;
        $photos = [];
        foreach (Db::run('SELECT id, path, alt FROM galerie_photos ORDER BY position, id LIMIT ' . self::PAR_PAGE . ' OFFSET ' . $debut)->fetchAll() as $i => $p) {
            $fichier = ROOT . '/public' . $p['path'];
            $taille = is_file($fichier) ? getimagesize($fichier) : false;
            $photos[] = $p + [
                'numero' => $debut + $i + 1,
                'largeur' => $taille ? $taille[0] : 1200,
                'hauteur' => $taille ? $taille[1] : 800,
            ];
        }
        $t = Contenu::traduire('page', '/galerie/', Pages::textes('/galerie/'));
        View::render('galerie/liste', [
            'meta' => ['title' => $t['titre'] . ($page > 1 ? ' (page ' . $page . ')' : ''), 'description' => $t['description'], 'h1' => $t['titre']],
            'path' => '/galerie/',
            'disponibles' => Contenu::disponibles('page', '/galerie/'),
            't' => $t,
            'photos' => $photos,
            'total' => $total,
            'page' => $page,
            'pages' => $pages,
            'styles' => ['/assets/css/galerie.css'],
            'scripts' => ['/assets/js/galerie.js'],
        ], 'site/layout');
    }
}
