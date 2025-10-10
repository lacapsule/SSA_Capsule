<?php

declare(strict_types=1);

namespace App\Navigation;

/**
 * Fournit les liens de la sidebar en fonction du rôle de l'utilisateur.
 */

final class SidebarLinksProvider
{
    /**
     * Retourne les liens de la sidebar.
     *
     * @param bool $isAdmin Indique si l'utilisateur est administrateur.
     * @return array<int, array{title:string,url:string,icon:string}> Liste des liens avec titre, URL et icône.
     */

    public function get(bool $isAdmin): array
    {

        $links = [
            ['title' => 'Accueil', 'url' => '/', 'icon' => 'home'],
            ['title' => 'Utilisateurs', 'url' => '/dashboard/users', 'icon' => 'users'],
            ['title' => 'Mes articles', 'url' => '/dashboard/articles', 'icon' => 'articles'],
            ['title' => 'Mot de passe', 'url' => '/dashboard/account', 'icon' => 'account'],
            ['title' => 'Agenda', 'url' => '/dashboard/agenda', 'icon' => 'calendar'],
            ['title' => 'Déconnexion', 'url' => '/logout', 'icon' => 'logout'],
        ];

        return $isAdmin ? $links : array_values(array_filter($links, fn ($l) => $l['url'] !== '/dashboard/users'));
    }
}
