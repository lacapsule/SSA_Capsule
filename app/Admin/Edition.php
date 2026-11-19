<?php
declare(strict_types=1);

namespace App\Admin;

use App\Core\Db;
use App\Core\View;

/**
 * Outils communs aux rubriques écrites pour le projet (actualités, agenda, galerie) : adresse lisible unique d'une
 * fiche, liens saisis par l'éditeur, dates et heures, rendu dans la coque du tableau de bord.
 */
final class Edition
{
    /** Tables qui ont une adresse lisible (colonne slug) : seules acceptées dans le SQL. */
    private const TABLES_SLUG = ['articles', 'events'];

    /** Adresse lisible depuis un titre : minuscules ASCII, mots séparés par des tirets (rule web-architecture). */
    public static function slug(string $texte): string
    {
        $t = function_exists('transliterator_transliterate')
            ? (string) transliterator_transliterate('Any-Latin; Latin-ASCII; Lower()', $texte)
            : strtolower($texte);
        $t = trim((string) preg_replace('/[^a-z0-9]+/', '-', $t), '-');
        return substr($t, 0, 120) ?: 'fiche';
    }

    /**
     * Adresse lisible unique dans la table (suffixe -2, -3… en cas de doublon). Posée à la création seulement : un titre
     * corrigé ensuite ne change pas l'adresse, un lien déjà partagé reste valable.
     */
    public static function slugUnique(string $table, string $titre): string
    {
        if (!in_array($table, self::TABLES_SLUG, true)) {
            throw new \LogicException("Table sans adresse lisible : $table");
        }
        $base = self::slug($titre);
        for ($n = 1; ; $n++) {
            $essai = $n === 1 ? $base : "$base-$n";
            if (!Db::run("SELECT 1 FROM $table WHERE slug = ?", [$essai])->fetchColumn()) {
                return $essai;
            }
        }
    }

    /**
     * Lien saisi par un éditeur : adresse web complète (http ou https) ou chemin du site (/…) ; refuse javascript:,
     * data:, vbscript: et tout autre schéma (rule lecons-securite, « Entrées et requêtes »).
     */
    public static function lienValide(string $url): bool
    {
        $url = trim($url);
        if ($url === '' || preg_match('/[\x00-\x20]/', $url) === 1) {
            return false;
        }
        if (str_starts_with($url, '/')) {
            return !str_starts_with($url, '//') && !str_starts_with($url, '/\\');
        }
        return preg_match('#^https?://[^/\s]+\.[^/\s]+#i', $url) === 1 && filter_var($url, FILTER_VALIDATE_URL) !== false;
    }

    /**
     * Liens d'une zone de texte, une adresse par ligne. @return array{0: list<string>, 1: ?string} liens gardés, erreur
     */
    public static function liensParLigne(string $saisie, int $max = 10): array
    {
        $liens = array_values(array_filter(array_map('trim', preg_split('/\R/', $saisie) ?: []), static fn (string $l): bool => $l !== ''));
        foreach ($liens as $l) {
            if (!self::lienValide($l)) {
                return [$liens, 'Adresse non valable : « ' . mb_strimwidth($l, 0, 60, '…') . ' ». Écrivez l\'adresse complète, commençant par https://.'];
            }
        }
        if (count($liens) > $max) {
            return [$liens, "$max liens au plus."];
        }
        return [$liens, null];
    }

    /** Date AAAA-MM-JJ valable (calendrier compris). */
    public static function dateValide(string $date): bool
    {
        $d = \DateTimeImmutable::createFromFormat('!Y-m-d', $date);
        return $d !== false && $d->format('Y-m-d') === $date;
    }

    /** Heure HH:MM valable. */
    public static function heureValide(string $heure): bool
    {
        return preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $heure) === 1;
    }

    /** Écran du tableau de bord dans la coque (gabarits/tableau-de-bord-php). @param array<string, mixed> $data */
    public static function rendre(string $gabarit, array $data): void
    {
        View::render($gabarit, $data, (string) config('admin.layout', 'admin/layout'));
    }
}
