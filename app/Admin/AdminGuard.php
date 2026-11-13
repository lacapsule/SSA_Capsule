<?php
declare(strict_types=1);

namespace App\Admin;

/**
 * Contrat de la garde d'administration (voir NOTES.md du socle, « Contrat des modules »).
 * Un module d'authentification fournit une implémentation ; les modules de contenu n'appellent que la façade Admin.
 * Adaptateurs fournis : TwoFactorGuard (modules/authentification-admin), InvitationGuard (modules/comptes-admin-invitation),
 * RolesGuard (modules/roles-et-journal).
 */
interface AdminGuard
{
    /**
     * Compte entièrement connecté, sinon null.
     * Forme commune : ['id' => int, 'email' => string, 'name' => string, 'role' => 'admin'|'editor'].
     * Une garde sans rôles renvoie toujours 'admin'.
     * @return array{id: int, email: string, name: string, role: string}|null
     */
    public function current(): ?array;

    /** Adresse de la page de connexion (chemin local, slash final). */
    public function loginUrl(): string;

    /** Adresse du formulaire POST de déconnexion (chemin local, slash final). */
    public function logoutUrl(): string;
}
