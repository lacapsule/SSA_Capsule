<?php

declare(strict_types=1);

namespace Capsule\Http\Support;

/**
 * FormState - Stockage éphémère des erreurs et données de formulaire
 *
 * Destiné au pattern PRG (Post-Redirect-Get) pour pré-remplir les formulaires
 * après une validation échouée.
 *
 * Contrat :
 * - set($errors, $data) : écrit deux clés dédiées en session
 * - consumeErrors() / consumeData() : lisent puis SUPPRIMENT (one-shot)
 *
 * Invariants :
 * - $errors : array<string,string> (messages lisibles, localisés)
 * - $data   : array<string,mixed>  (valeurs pré-normalisées)
 *
 * Sécurité :
 * - TRUST BOUNDARY : $data provient de l'utilisateur
 * - Valider/normaliser AVANT d'appeler set()
 * - Ne JAMAIS stocker de mots de passe en clair
 * - Filtrer les champs sensibles (ex: 'password', 'token')
 *
 * Performance :
 * - Garder $data compact (éviter gros payloads)
 * - Sessions files/DB peuvent être impactées
 *
 * UX :
 * - "consume" = destructive : un onglet peut vider l'état d'un autre
 * - Fournir peek() si besoin de lecture non-destructive
 */
final class FormState
{
    private const ERRORS = '__form_errors';
    private const DATA = '__form_data';

    /**
     * Écrit l'état du formulaire (erreurs + données) en session
     *
     * @param array<string,string> $errors Messages d'erreur par champ
     * @param array<string,mixed>  $data   Données normalisées à ré-afficher
     */
    public static function set(array $errors, array $data): void
    {
        SessionManager::set(self::ERRORS, $errors);
        SessionManager::set(self::DATA, $data);
    }

    /**
     * Lit puis supprime les erreurs (one-shot)
     *
     * @return array<string,string>|null null si rien en session
     */
    public static function consumeErrors(): ?array
    {
        $errors = SessionManager::get(self::ERRORS);
        SessionManager::remove(self::ERRORS);

        return is_array($errors) ? $errors : null;
    }

    /**
     * Lit puis supprime les données (one-shot)
     *
     * @return array<string,mixed>|null null si rien en session
     */
    public static function consumeData(): ?array
    {
        $data = SessionManager::get(self::DATA);
        SessionManager::remove(self::DATA);

        return is_array($data) ? $data : null;
    }

    /**
     * Récupère les erreurs SANS les consommer (utile pour debug)
     *
     * @return array<string,string>|null
     */
    public static function peekErrors(): ?array
    {
        $errors = SessionManager::get(self::ERRORS);

        return is_array($errors) ? $errors : null;
    }

    /**
     * Récupère les données SANS les consommer (utile pour debug)
     *
     * @return array<string,mixed>|null
     */
    public static function peekData(): ?array
    {
        $data = SessionManager::get(self::DATA);

        return is_array($data) ? $data : null;
    }

    /**
     * Vide l'état du formulaire (erreurs + données)
     */
    public static function clear(): void
    {
        SessionManager::remove(self::ERRORS);
        SessionManager::remove(self::DATA);
    }
}
