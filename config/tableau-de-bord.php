<?php
declare(strict_types=1);

// Accueil du tableau de bord. « role » => 'admin' réserve une entrée aux administrateurs.
// chiffres : requête SQL fixe qui renvoie un nombre (écrite ici, jamais construite depuis une saisie).
return [
    'raccourcis' => [
        ['href' => '/admin/actualites/nouveau/', 'label' => 'Écrire un article', 'icon' => 'edit', 'principal' => true],
        ['href' => '/admin/agenda/nouveau/', 'label' => 'Ajouter un événement', 'icon' => 'plus'],
        ['href' => '/admin/galerie/', 'label' => 'Ajouter des photos', 'icon' => 'upload'],
        ['href' => '/', 'label' => 'Voir le site', 'icon' => 'eye'],
    ],
    'chiffres' => [
        ['href' => '/admin/actualites/', 'label' => 'Actualités en ligne', 'sql' => 'SELECT COUNT(*) FROM articles WHERE is_published = 1'],
        ['href' => '/admin/agenda/', 'label' => 'Événements à venir', 'sql' => "SELECT COUNT(*) FROM events WHERE is_published = 1 AND ends_at >= strftime('%Y-%m-%d %H:%M', 'now', 'localtime')"],
        ['href' => '/admin/galerie/', 'label' => 'Photos de la galerie', 'sql' => 'SELECT COUNT(*) FROM galerie_photos'],
        ['href' => '/admin/lieux', 'label' => 'Lieux conventionnés', 'sql' => "SELECT COUNT(*) FROM lieux_conventionnes WHERE is_published = 1 AND statut = 'ok'"],
    ],
];
