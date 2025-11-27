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

            ['title' => 'Mes articles', 'url' => '/dashboard/articles', 'icon' => 'articles'],
            ['title' => 'Mon Agenda', 'url' => '/dashboard/agenda', 'icon' => 'calendar'],
            ['title' => 'Ma galerie', 'url' => '/dashboard/galerie', 'icon' => 'galerie'],
            ['title' => 'Partenaires', 'url' => '/dashboard/partners', 'icon' => 'galerie'],
            
            ['title' => 'Utilisateurs', 'url' => '/dashboard/users', 'icon' => 'users'],
            ['title' => 'Mon profil', 'url' => '/dashboard/account', 'icon' => 'profil'],
            
            ['title'=> 'Allez au site', 'url' => '/', 'icon'=> 'site'],
            ['title' => 'Déconnexion', 'url' => '/logout', 'icon' => 'logout'],
        ];

        // Détecter le chemin courant (sans query string)
        $current = $_SERVER['REQUEST_URI'] ?? '';
        $currentPath = parse_url($current, PHP_URL_PATH) ?: '/';

        // Normaliser et ajouter un flag `active` selon l'URL courante
        $normalized = array_map(function ($l) use ($currentPath) {
            $l['active'] = false;
            $linkPath = parse_url($l['url'], PHP_URL_PATH) ?: $l['url'];

            // Marquer actif si égalité exacte ou si la route courante commence par l'url (ex: /dashboard/articles/123)
            if ($linkPath === $currentPath || str_starts_with($currentPath, rtrim($linkPath, '/') . '/')) {
                $l['active'] = true;
            }

            return $l;
        }, $links);

        $filtered = $isAdmin
            ? $normalized
            : array_values(array_filter(
                $normalized,
                fn ($l) => !in_array($l['url'], ['/dashboard/users', '/dashboard/partners'], true)
            ));

        return $filtered;
    }
}
