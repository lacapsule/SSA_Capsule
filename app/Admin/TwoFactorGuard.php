<?php
declare(strict_types=1);

namespace App\Admin;

/**
 * Adaptateur de la garde commune (gabarits/socle-php, App\Admin\AdminGuard) pour la connexion à double authentification.
 * Dans config/app.php : 'admin' => ['guard' => App\Admin\TwoFactorGuard::class].
 * Le compte renvoyé n'a jamais de secret (hachage, secret TOTP) ; role et perimetre suivent s'ils existent (roles-et-journal).
 */
final class TwoFactorGuard implements AdminGuard
{
    public function current(): ?array
    {
        return AdminAuth::current();
    }

    public function loginUrl(): string
    {
        return AdminController::url('connexion/');
    }

    public function logoutUrl(): string
    {
        return AdminController::url('deconnexion/');
    }
}
