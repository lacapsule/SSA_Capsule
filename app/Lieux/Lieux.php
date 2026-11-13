<?php
declare(strict_types=1);

namespace App\Lieux;

use App\Core\Db;
use App\Core\View;
use App\Langue\Contenu;
use App\Pages\Pages;

/** Lieux conventionnés (maquette docs/maquettes/lieux-conventionnes.md) : texte repris de l'article 16, liste en base. */
final class Lieux
{
    public static function liste(): void
    {
        $lieux = Contenu::traduireListe('lieu', Db::run('SELECT id, nom, adresse, code_postal, ville, horaires FROM lieux_conventionnes WHERE is_published = 1 ORDER BY position, nom')->fetchAll());
        $t = Contenu::traduire('page', '/lieux-conventionnes/', Pages::textes('/lieux-conventionnes/'));
        View::render('lieux/liste', [
            'meta' => ['title' => $t['title'], 'description' => $t['description'], 'h1' => $t['titre']],
            'path' => '/lieux-conventionnes/',
            'disponibles' => Contenu::disponibles('page', '/lieux-conventionnes/'),
            't' => $t,
            'lieux' => $lieux,
        ], 'site/layout');
    }
}
