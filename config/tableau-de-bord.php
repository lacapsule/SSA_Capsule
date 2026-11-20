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
    // a_faire : travail en attente, montré seulement quand la requête renvoie plus de 0 (libellé : singulier, pluriel).
    'a_faire' => [
        ['href' => '/admin/photos-a-decrire/', 'label' => ['photo sans description', 'photos sans description'], 'action' => 'Les décrire', 'sql' => "SELECT (SELECT COUNT(*) FROM galerie_photos WHERE TRIM(alt) = '') + (SELECT COUNT(*) FROM articles WHERE COALESCE(image, '') <> '' AND TRIM(COALESCE(image_alt, '')) = '' AND image_decorative = 0)"],
        ['href' => '/admin/lieux', 'label' => ['lieu absent de la carte', 'lieux absents de la carte'], 'action' => 'Corriger leur adresse', 'sql' => 'SELECT COUNT(*) FROM lieux_conventionnes WHERE latitude IS NULL'],
        ['href' => '/admin/lieux', 'label' => ['lieu sans adresse', 'lieux sans adresse'], 'action' => 'Compléter', 'sql' => "SELECT COUNT(*) FROM lieux_conventionnes WHERE latitude IS NOT NULL AND TRIM(COALESCE(adresse, '')) = ''"],
    ],
    'chiffres' => [
        ['href' => '/admin/actualites/', 'label' => 'Actualités en ligne', 'sql' => 'SELECT COUNT(*) FROM articles WHERE is_published = 1'],
        ['href' => '/admin/agenda/', 'label' => 'Événements à venir', 'sql' => "SELECT COUNT(*) FROM events WHERE is_published = 1 AND ends_at >= strftime('%Y-%m-%d %H:%M', 'now', 'localtime')"],
        ['href' => '/admin/galerie/', 'label' => 'Photos de la galerie', 'sql' => 'SELECT COUNT(*) FROM galerie_photos'],
        ['href' => '/admin/lieux', 'label' => 'Lieux conventionnés', 'sql' => "SELECT COUNT(*) FROM lieux_conventionnes WHERE is_published = 1 AND statut = 'ok'"],
    ],
];
