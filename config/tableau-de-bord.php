<?php
declare(strict_types=1);

// Accueil du tableau de bord. « role » => 'admin' réserve une entrée aux administrateurs.
// chiffres : requête SQL fixe qui renvoie un nombre (écrite ici, jamais construite depuis une saisie).
// Raccourcis des rubriques (actualités, agenda, galerie, partenaires, lieux) : ajoutés avec leur module (phase 2).
return [
    'raccourcis' => [
        ['href' => '/', 'label' => 'Voir le site', 'icon' => 'eye'],
        ['href' => '/admin/securite/', 'label' => 'Sécurité du compte', 'icon' => 'key'],
    ],
    'chiffres' => [
        ['href' => '/actualites/', 'label' => 'Actualités en ligne', 'sql' => 'SELECT COUNT(*) FROM articles WHERE is_published = 1'],
        ['href' => '/agenda/', 'label' => 'Événements à venir', 'sql' => "SELECT COUNT(*) FROM events WHERE is_published = 1 AND ends_at >= strftime('%Y-%m-%d %H:%M', 'now', 'localtime')"],
        ['href' => '/galerie/', 'label' => 'Photos de la galerie', 'sql' => 'SELECT COUNT(*) FROM galerie_photos'],
        ['href' => '/lieux-conventionnes/', 'label' => 'Lieux conventionnés', 'sql' => "SELECT COUNT(*) FROM lieux_conventionnes WHERE is_published = 1 AND statut = 'ok'"],
    ],
];
