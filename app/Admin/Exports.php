<?php
declare(strict_types=1);

namespace App\Admin;

use App\Core\Auth;
use App\Core\Db;
use App\Core\Export;

/**
 * Exports tableur du tableau de bord (modules/export-tableur) : lieux conventionnés et agenda, en Excel ou en CSV.
 * Cellules neutralisées contre les formules par App\Core\Export (rule lecons-securite, « Données et fichiers »).
 * Adresses de fichier sans slash final (jamais redirigées par le socle).
 */
final class Exports
{
    private const STATUTS = ['ok' => 'Conventionné', 'attente' => 'En attente', 'refus' => 'Refusé', 'sans' => 'Sans conventionnement'];

    public static function lieux(): void
    {
        Admin::page();
        $format = self::format();
        $requete = Db::run('SELECT nom, statut, adresse, code_postal, ville, description, horaires, latitude, longitude, is_published FROM lieux_conventionnes ORDER BY ville, nom');
        $lignes = (static function () use ($requete): \Generator {
            yield ['Nom', 'Statut', 'Adresse', 'Code postal', 'Commune', 'Produits et informations', 'Horaires', 'Latitude', 'Longitude', 'En ligne'];
            foreach ($requete as $r) {
                yield [(string) $r['nom'], self::STATUTS[$r['statut']] ?? (string) $r['statut'], (string) $r['adresse'], (string) $r['code_postal'], (string) $r['ville'],
                    (string) ($r['description'] ?? ''), (string) ($r['horaires'] ?? ''), $r['latitude'] !== null ? (float) $r['latitude'] : '', $r['longitude'] !== null ? (float) $r['longitude'] : '', (int) $r['is_published'] === 1 ? 'oui' : 'non'];
            }
        })();
        Auth::log('lieux.export', $format);
        Export::telecharger('Lieux conventionnés ' . date('Y-m-d'), $format, $lignes, [], 'Lieux');
        exit;
    }

    public static function agenda(): void
    {
        Admin::page();
        $format = self::format();
        $publics = array_map(static fn (string $cle): string => t($cle), \App\Agenda\Agenda::CATEGORIES);
        $requete = Db::run('SELECT title, starts_at, ends_at, category, address, description, links, is_published FROM events ORDER BY starts_at DESC');
        $lignes = (static function () use ($requete, $publics): \Generator {
            yield ['Titre', 'Début', 'Fin', 'Public', 'Lieu', 'Description', 'Liens', 'En ligne'];
            foreach ($requete as $r) {
                yield [(string) $r['title'], (string) $r['starts_at'] . ':00', (string) $r['ends_at'] . ':00', $publics[$r['category']] ?? '', (string) ($r['address'] ?? ''),
                    (string) ($r['description'] ?? ''), (string) ($r['links'] ?? ''), (int) $r['is_published'] === 1 ? 'oui' : 'non'];
            }
        })();
        Auth::log('agenda.export', $format);
        Export::telecharger('Agenda ' . date('Y-m-d'), $format, $lignes, ['formats' => ['Début' => 'dateheure', 'Fin' => 'dateheure']], 'Agenda');
        exit;
    }

    /** Excel seulement si l'extension zip de PHP est là (sinon le classeur ne peut pas s'écrire) : CSV sinon. */
    public static function excelPossible(): bool
    {
        return class_exists(\ZipArchive::class);
    }

    private static function format(): string
    {
        return ($_GET['format'] ?? '') === 'csv' || !self::excelPossible() ? 'csv' : 'xlsx';
    }
}
