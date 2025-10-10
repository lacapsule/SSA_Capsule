<?php

declare(strict_types=1);

namespace Capsule\Http\Support;

/**
 * FormState
 * ----------
 * Stockage éphémère (session) des erreurs et des données issues d'un POST,
 * destiné au pattern PRG (Post/Redirect/Get).
 *
 * Contrat:
 *  - `set($errors, $data)` écrit deux clés dédiées dans la session.
 *  - `consumeErrors()` et `consumeData()` lisent puis SUPPRIMENT la valeur (one-shot).
 *
 * Invariants:
 *  - $errors: array<string, string> (messages lisibles, déjà localisés côté appelant).
 *  - $data:   array<string, mixed>  (valeurs pré-normalisées; ne jamais y mettre des secrets).
 *
 * Sécu:
 *  - TRUST BOUNDARY: $data provient de l’utilisateur → valider/normaliser avant d’appeler set().
 *  - Ne pas stocker de mots de passe en clair; filtrer les champs sensibles (ex: 'password').
 *  - Exige une session active (session_status() === PHP_SESSION_ACTIVE).
 *
 * Perf:
 *  - Garder $data compact (éviter gros payloads) → sessions files/DB peuvent être impactées.
 *
 * Concurrence/UX:
 *  - "consume" = destructive: un onglet A peut vider l’état attendu par l’onglet B.
 *    Fournir un "peek" si nécessaire (hors de cette classe).
 */
final class FormState
{
    private const ERRORS = '__form_errors';
    private const DATA = '__form_data';

    /**
     * Écrit l'état du formulaire (erreurs + données) en session.
     *
     * @param array<string,string> $errors  Messages d'erreur par champ (ou clés globales)
     * @param array<string,mixed>  $data    Données normalisées à ré-afficher
     */
    public static function set(array $errors, array $data): void
    {
        // GUARD: session requise
        if (\PHP_SESSION_ACTIVE !== \session_status()) {
            throw new \RuntimeException('FormState requires an active session.');
        }

        $_SESSION[self::ERRORS] = $errors;
        $_SESSION[self::DATA] = $data;
    }

    /**
     * Lit puis supprime les erreurs.
     *
     * @return array<string,string>|null null si rien en session
     */
    public static function consumeErrors(): ?array
    {
        if (\PHP_SESSION_ACTIVE !== \session_status()) {
            throw new \RuntimeException('FormState requires an active session.');
        }

        /** @var array<string,string>|null $e */
        $e = $_SESSION[self::ERRORS] ?? null;
        unset($_SESSION[self::ERRORS]);

        return $e;
    }

    /**
     * Lit puis supprime les données.
     *
     * @return array<string,mixed>|null null si rien en session
     */
    public static function consumeData(): ?array
    {
        if (\PHP_SESSION_ACTIVE !== \session_status()) {
            throw new \RuntimeException('FormState requires an active session.');
        }

        /** @var array<string,mixed>|null $d */
        $d = $_SESSION[self::DATA] ?? null;
        unset($_SESSION[self::DATA]);

        return $d;
    }
}
