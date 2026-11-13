<?php
declare(strict_types=1);

namespace App\Actualites;

use App\Core\Db;
use App\Core\View;
use App\Langue\Contenu;
use App\Pages\Pages;
use App\Site\Dates;
use App\Site\Texte;

/** Actualités publiques (maquettes actualites.md et actualites-fiche.md), version bretonne en table traductions. */
final class Actualites
{
    private const CHAMPS = 'id, titre, resume, description, date_article, image, image_alt, slug, lieu, heure, liens, videos';
    private const PAR_PAGE = 9;

    private static function aujourdhui(): string
    {
        return (new \DateTimeImmutable('now', new \DateTimeZone('Europe/Paris')))->format('Y-m-d');
    }

    /** @return list<array<string, mixed>> */
    public static function dernieres(int $n): array
    {
        $lignes = Db::run('SELECT ' . self::CHAMPS . ' FROM articles WHERE is_published = 1 AND date_article <= ? ORDER BY date_article DESC, id DESC LIMIT ' . $n, [self::aujourdhui()])->fetchAll();
        return Contenu::traduireListe('article', $lignes);
    }

    public static function liste(): void
    {
        $total = (int) Db::run('SELECT COUNT(*) FROM articles WHERE is_published = 1 AND date_article <= ?', [self::aujourdhui()])->fetchColumn();
        $pages = max(1, (int) ceil($total / self::PAR_PAGE));
        $page = filter_var($_GET['page'] ?? 1, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: 1;
        if ($page > $pages) {
            abort(404);
        }
        $lignes = Db::run('SELECT ' . self::CHAMPS . ' FROM articles WHERE is_published = 1 AND date_article <= ? ORDER BY date_article DESC, id DESC LIMIT ' . self::PAR_PAGE . ' OFFSET ' . (($page - 1) * self::PAR_PAGE), [self::aujourdhui()])->fetchAll();
        $t = Contenu::traduire('page', '/actualites/', Pages::textes('/actualites/'));
        View::render('actualites/liste', [
            'meta' => ['title' => $t['titre'], 'description' => $t['description'], 'h1' => $t['titre']],
            'path' => '/actualites/',
            'disponibles' => Contenu::disponibles('page', '/actualites/'),
            't' => $t,
            'articles' => Contenu::traduireListe('article', $lignes),
            'page' => $page,
            'pages' => $pages,
        ], 'site/layout');
    }

    public static function fiche(string $slug): void
    {
        $a = Db::run('SELECT ' . self::CHAMPS . ' FROM articles WHERE is_published = 1 AND date_article <= ? AND slug = ?', [self::aujourdhui(), $slug])->fetch();
        if ($a === false) {
            abort(404);
        }
        $t = Contenu::traduire('article', (string) $a['id'], $a);
        $images = Db::run('SELECT path, alt FROM article_images WHERE article_id = ? ORDER BY position', [$a['id']])->fetchAll();
        $voisin = static fn (string $op, string $ordre) => Db::run("SELECT titre, slug, image, videos FROM articles WHERE is_published = 1 AND date_article <= ? AND (date_article $op ? OR (date_article = ? AND id $op ?)) ORDER BY date_article $ordre, id $ordre LIMIT 1", [self::aujourdhui(), $a['date_article'], $a['date_article'], $a['id']])->fetch() ?: null;
        View::render('actualites/fiche', [
            'meta' => ['title' => $t['titre'], 'description' => Texte::couper((string) ($t['resume'] ?: $t['description']), 158), 'h1' => $t['titre']],
            'path' => '/actualites/' . $slug . '/',
            'disponibles' => Contenu::disponibles('article', (string) $a['id']),
            'a' => $t,
            'images' => $images,
            'liens' => json_decode((string) ($a['liens'] ?? ''), true) ?: [],
            'videos' => json_decode((string) ($a['videos'] ?? ''), true) ?: [],
            'precedent' => $voisin('<', 'DESC'),
            'suivant' => $voisin('>', 'ASC'),
        ], 'site/layout');
    }

    /** @return list<array{chemin: string, langues: list<string>, modifie: ?string}> entrées du sitemap */
    public static function plan(): array
    {
        return array_map(static fn (array $a): array => ['chemin' => '/actualites/' . $a['slug'] . '/', 'langues' => Contenu::disponibles('article', (string) $a['id']), 'modifie' => $a['updated_at'] ? substr((string) $a['updated_at'], 0, 10) : null],
            Db::run('SELECT id, slug, updated_at FROM articles WHERE is_published = 1 AND date_article <= ?', [self::aujourdhui()])->fetchAll());
    }

    /** Date de l'article dans la langue de la page. */
    public static function date(array $a): string
    {
        return Dates::longue((string) $a['date_article']);
    }
}
