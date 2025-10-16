<?php

declare(strict_types=1);

namespace App\Modules\Login;

/**
 * LoginPresenter - Projection des données pour la vue de connexion
 *
 * Responsabilités :
 * - Formater les données pour le template Mustache
 * - Gérer les erreurs et le pré-remplissage
 */
final class LoginPresenter
{
    /**
     * Prépare les données pour le formulaire de connexion
     *
     * @param array<string,string> $i18n Traductions
     * @param array<string,string> $errors Erreurs de validation
     * @param array<string,mixed> $prefill Données pré-remplies
     * @param string $csrfInput Champ CSRF HTML
     * @return array<string,mixed> Données pour le template
     */
    public static function loginForm(
        array $i18n,
        array $errors,
        array $prefill,
        string $csrfInput
    ): array {
        return [
            'title' => $i18n['login_title'] ?? 'Connexion',
            'str' => $i18n,

            // Erreur globale (affichée en haut du formulaire)
            'error' => $errors['_global'] ?? null,

            // Toutes les erreurs (pour affichage par champ si besoin)
            'errors' => $errors,

            // Pré-remplissage du formulaire
            'prefill' => [
                'username' => (string) ($prefill['username'] ?? ''),
            ],

            // Token CSRF
            'csrf_input' => $csrfInput,

            // Action du formulaire
            'action' => '/login',
        ];
    }
}
