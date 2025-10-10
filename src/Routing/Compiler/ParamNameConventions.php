<?php

declare(strict_types=1);

namespace Capsule\Routing\Compiler;

/**
 * ParamNameConventions
 *
 * Rôle : fournir la regex par défaut pour un paramètre de chemin
 * en fonction de son **nom** (conventions).
 *
 * Exemples :
 *  - {id}    → \d+
 *  - {slug}  → [a-z0-9-]+
 *  - {uuid}  → UUID v4
 *  - sinon   → [^/]+ (un segment sans '/')
 *
 * Invariants :
 *  - Retourne une regex SANS délimiteurs (pas de #...#).
 *  - Aucune I/O, purement déterministe.
 *  - Facilement extensible (ajoute un case dans le match).
 */
final class ParamNameConventions
{
    /**
     * Donne le motif regex (sans délimiteurs) attendu pour un paramètre de chemin
     * en fonction de son nom.
     */
    public static function regexFor(string $name): string
    {
        $n = strtolower($name);

        return match ($n) {
            'id', 'page', 'perpage', 'year' => '\d+',
            'slug' => '[a-z0-9-]+',
            'uuid' => '[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[1-5][0-9a-fA-F]{3}-[89abAB][0-9a-fA-F]{3}-[0-9a-fA-F]{12}',
            default => '[^/]+',
        };
    }
}
