<?php

declare(strict_types=1);

namespace Capsule\Http\Support;

/**
 * FlashBag
 * --------
 * Messages éphémères “flash” (succès/erreur/etc.) maintenus en session
 * jusqu’à la prochaine requête, typiquement pour le pattern PRG.
 *
 * Contrat:
 *  - `add($type, $msg)` empile un message sous une clé de type.
 *  - `consume()` retourne toutes les piles de messages PUIS les supprime (one-shot).
 *
 * Types:
 *  - $type arbitraire (ex: 'success', 'error', 'info', 'warning').
 *    → Recommandé: restreindre à un ENUM applicatif (cf. section B).
 *
 * Sécu:
 *  - Contenu destiné à la vue → encoder côté template (XSS).
 *  - Session active requise.
 */
final class FlashBag
{
    private const KEY = '__flash';

    /**
     * Ajoute un message éphémère sous un type donné.
     *
     * @param non-empty-string $type   Ex: 'success'|'error'|'info'|'warning'
     * @param non-empty-string $msg    Message lisible (déjà localisé)
     */
    public static function add(string $type, string $msg): void
    {
        if (\PHP_SESSION_ACTIVE !== \session_status()) {
            throw new \RuntimeException('FlashBag requires an active session.');
        }

        // INIT paresseuse de la structure
        if (!isset($_SESSION[self::KEY]) || !\is_array($_SESSION[self::KEY])) {
            $_SESSION[self::KEY] = [];
        }
        if (!isset($_SESSION[self::KEY][$type]) || !\is_array($_SESSION[self::KEY][$type])) {
            $_SESSION[self::KEY][$type] = [];
        }

        $_SESSION[self::KEY][$type][] = $msg;
    }

    /**
     * Retourne toutes les piles de messages et purge la clé.
     *
     * @return array{success?:string[], error?:string[], info?:string[], warning?:string[]}
     */
    public static function consume(): array
    {
        if (\PHP_SESSION_ACTIVE !== \session_status()) {
            throw new \RuntimeException('FlashBag requires an active session.');
        }

        /** @var array<string, string[]> $all */
        $all = isset($_SESSION[self::KEY]) && \is_array($_SESSION[self::KEY])
            ? $_SESSION[self::KEY]
            : [];

        unset($_SESSION[self::KEY]);

        return $all;
    }
}
