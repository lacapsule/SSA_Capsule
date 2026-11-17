<?php
declare(strict_types=1);

namespace App\Lieux;

use App\Core\Db;
use App\Core\View;
use App\Langue\Contenu;
use App\Pages\Pages;

/** Lieux conventionnés (maquette docs/maquettes/lieux-conventionnes.md) : texte repris de l'article 16, liste en base,
 * regroupée par commune (Groupes::parCommune). */
final class Lieux
{
    public static function liste(): void
    {
        self::afficher(false);
    }

    /** Page de la carte (ouverte depuis la page des lieux, nouvel onglet) : carte en grand et panneau des lieux, non indexée. */
    public static function carte(): void
    {
        self::afficher(true);
    }

    private static function afficher(bool $pageCarte): void
    {
        $lieux = Contenu::traduireListe('lieu', Db::run('SELECT id, nom, adresse, code_postal, ville, horaires, statut, description, latitude, longitude FROM lieux_conventionnes WHERE is_published = 1 ORDER BY nom COLLATE NOCASE')->fetchAll());
        $t = Contenu::traduire('page', '/lieux-conventionnes/', Pages::textes('/lieux-conventionnes/'));
        View::render($pageCarte ? 'lieux/carte-grande' : 'lieux/liste', [
            'meta' => ['title' => $pageCarte ? $t['carte_page_titre'] : $t['title'], 'description' => $t['description'], 'h1' => $pageCarte ? $t['carte_page_titre'] : $t['titre']],
            'path' => $pageCarte ? '/lieux-conventionnes/carte/' : '/lieux-conventionnes/',
            'noindex' => $pageCarte,
            'disponibles' => Contenu::disponibles('page', '/lieux-conventionnes/'),
            't' => $t,
            // Quatre calques de la carte du collectif (décision du 2026-11-17) ; « refus » et « sans » décochés au départ.
            'lieux' => $lieux,
            'groupes' => Groupes::parCommune($lieux, (string) $t['autres_lieux']),
            'calques' => array_map(static fn (string $c): int => count(array_filter($lieux, static fn (array $l): bool => $l['statut'] === $c)), ['ok' => 'ok', 'attente' => 'attente', 'refus' => 'refus', 'sans' => 'sans']),
            'scripts' => ['/assets/js/lieux.js'],
        ], 'site/layout');
    }
}
