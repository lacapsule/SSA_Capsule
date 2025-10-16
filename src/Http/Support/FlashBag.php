<?php

declare(strict_types=1);

namespace Capsule\Http\Support;

/**
 * FlashBag - Messages éphémères flash (succès/erreur/etc.)
 *
 * Messages maintenus en session jusqu'à la prochaine requête,
 * typiquement pour le pattern PRG (Post-Redirect-Get).
 *
 * Contrat :
 * - add($type, $msg) : empile un message sous une clé de type
 * - consume() : retourne toutes les piles ET les supprime (one-shot)
 * - peek() : retourne sans consommer (utile pour debug)
 *
 * Types recommandés :
 * - 'success', 'error', 'info', 'warning'
 *
 * Sécurité :
 * - Contenu destiné à la vue → encoder côté template (XSS)
 * - Délègue la gestion session à SessionManager
 */
final class FlashBag
{
    private const KEY = '__flash';

    /**
     * Ajoute un message éphémère sous un type donné
     *
     * @param non-empty-string $type Ex: 'success'|'error'|'info'|'warning'
     * @param non-empty-string $msg  Message lisible (déjà localisé)
     */
    public static function add(string $type, string $msg): void
    {
        // Récupération de la structure existante
        $flash = SessionManager::get(self::KEY, []);

        // Init paresseuse de la pile pour ce type
        if (!isset($flash[$type]) || !is_array($flash[$type])) {
            $flash[$type] = [];
        }

        $flash[$type][] = $msg;

        SessionManager::set(self::KEY, $flash);
    }

    /**
     * Retourne toutes les piles de messages et purge la clé (one-shot)
     *
     * @return array<string,list<string>> Ex: ['success'=>['msg1','msg2'], 'error'=>['err1']]
     */
    public static function consume(): array
    {
        $all = SessionManager::get(self::KEY, []);
        SessionManager::remove(self::KEY);

        return is_array($all) ? $all : [];
    }

    /**
     * Retourne les messages SANS les consommer (utile pour debug)
     *
     * @return array<string,list<string>>
     */
    public static function peek(): array
    {
        $all = SessionManager::get(self::KEY, []);

        return is_array($all) ? $all : [];
    }

    /**
     * Vide tous les messages flash (sans consommer)
     */
    public static function clear(): void
    {
        SessionManager::remove(self::KEY);
    }
}
