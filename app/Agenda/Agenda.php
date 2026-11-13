<?php
declare(strict_types=1);

namespace App\Agenda;

use App\Core\Db;
use App\Core\View;
use App\Langue\Contenu;
use App\Pages\Pages;
use App\Site\Texte;

/** Agenda public (maquettes agenda.md et agenda-fiche.md) : événements publiés, version bretonne en table traductions. */
final class Agenda
{
    private const CHAMPS = 'id, title, starts_at, ends_at, address, description, category, slug, links';
    // Catégories du site actuel : clé => clé du libellé d'interface.
    public const CATEGORIES = ['collectif' => 'agenda.cat_collectif', 'experimentateurs' => 'agenda.cat_experimentateurs', 'pour_tous' => 'agenda.cat_pour_tous'];

    private static function maintenant(): string
    {
        return (new \DateTimeImmutable('now', new \DateTimeZone('Europe/Paris')))->format('Y-m-d H:i');
    }

    /** @return list<array<string, mixed>> */
    public static function aVenir(?int $limite = null, ?string $categorie = null): array
    {
        $sql = 'SELECT ' . self::CHAMPS . ' FROM events WHERE is_published = 1 AND ends_at >= ?' . ($categorie ? ' AND category = ?' : '') . ' ORDER BY starts_at';
        $lignes = Db::run($sql . ($limite ? ' LIMIT ' . $limite : ''), array_values(array_filter([self::maintenant(), $categorie])))->fetchAll();
        return Contenu::traduireListe('evenement', $lignes);
    }

    /**
     * Événements groupés par mois, libellé dans la langue de la page (« novembre 2026 »).
     * @param list<array<string, mixed>> $evenements @return array<string, list<array<string, mixed>>>
     */
    public static function parMois(array $evenements): array
    {
        $groupes = [];
        foreach ($evenements as $e) {
            $groupes[\App\Site\Dates::mois((string) $e['starts_at'])][] = $e;
        }
        return $groupes;
    }

    /** @return list<array<string, mixed>> */
    public static function passes(): array
    {
        $lignes = Db::run('SELECT ' . self::CHAMPS . ' FROM events WHERE is_published = 1 AND ends_at < ? ORDER BY starts_at DESC', [self::maintenant()])->fetchAll();
        return Contenu::traduireListe('evenement', $lignes);
    }

    public static function liste(): void
    {
        $categorie = $_GET['public'] ?? null;
        $categorie = is_string($categorie) && isset(self::CATEGORIES[$categorie]) ? $categorie : null;
        $t = Contenu::traduire('page', '/agenda/', Pages::textes('/agenda/'));
        View::render('agenda/liste', [
            'meta' => ['title' => $t['titre'], 'description' => $t['description'], 'h1' => $t['titre']],
            'path' => '/agenda/',
            'disponibles' => Contenu::disponibles('page', '/agenda/'),
            't' => $t,
            'categorie' => $categorie,
            'evenements' => self::aVenir(null, $categorie),
            'passes' => $categorie ? [] : self::passes(),
        ], 'site/layout');
    }

    public static function fiche(string $slug): void
    {
        $e = Db::run('SELECT ' . self::CHAMPS . ' FROM events WHERE is_published = 1 AND slug = ?', [$slug])->fetch();
        if ($e === false) {
            abort(404);
        }
        $t = Contenu::traduire('evenement', (string) $e['id'], $e);
        $voisin = static fn (string $op, string $ordre) => Db::run("SELECT title, slug FROM events WHERE is_published = 1 AND starts_at $op ? ORDER BY starts_at $ordre LIMIT 1", [$e['starts_at']])->fetch() ?: null;
        View::render('agenda/fiche', [
            'meta' => ['title' => $t['title'], 'description' => self::resume($t), 'h1' => $t['title']],
            'path' => '/agenda/' . $slug . '/',
            'disponibles' => Contenu::disponibles('evenement', (string) $e['id'], 'title'),
            'e' => $t,
            'precedent' => $voisin('<', 'DESC'),
            'suivant' => $voisin('>', 'ASC'),
        ], 'site/layout');
    }

    /** Description courte pour la balise meta : date, lieu, début du texte. */
    private static function resume(array $e): string
    {
        $debut = \App\Site\Dates::longue($e['starts_at']) . ', ' . \App\Site\Dates::heure($e['starts_at']) . ($e['address'] ? ', ' . $e['address'] : '') . '. ';
        $texte = $debut . preg_replace('/\s+/', ' ', (string) $e['description']);
        return Texte::couper($texte, 158);
    }

    /** @return list<array{chemin: string, langues: list<string>, modifie: ?string}> entrées du sitemap */
    public static function plan(): array
    {
        return array_map(static fn (array $e): array => ['chemin' => '/agenda/' . $e['slug'] . '/', 'langues' => Contenu::disponibles('evenement', (string) $e['id'], 'title'), 'modifie' => null],
            Db::run('SELECT id, slug FROM events WHERE is_published = 1')->fetchAll());
    }
}
