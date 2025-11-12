<?php

declare(strict_types=1);

namespace App\Providers;

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
            ['title' => 'Accueil', 'url' => '/dashboard', 'icon' => 'dashboard'],

            ['title' => 'Mon Agenda', 'url' => '/dashboard/agenda', 'icon' => 'calendar'],
            ['title' => 'Utilisateurs', 'url' => '/dashboard/users', 'icon' => 'users'],
            ['title' => 'Mes articles', 'url' => '/dashboard/articles', 'icon' => 'articles'],
            ['title' => 'Ma galerie', 'url' => '/dashboard/galerie', 'icon' => 'galerie'],

            ['title' => 'Mon profil', 'url' => '/dashboard/account', 'icon' => 'profil'],
            
            ['title'=> 'Allez au site', 'url' => '/', 'icon'=> 'site'],
            ['title' => 'Déconnexion', 'url' => '/logout', 'icon' => 'logout'],
        ];

        return $isAdmin ? $links : array_values(array_filter($links, fn ($l) => $l['url'] !== '/dashboard/users'));
    }
}
