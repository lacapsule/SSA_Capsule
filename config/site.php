<?php
declare(strict_types=1);

// Identité et navigation du site public : source unique des coordonnées (pages, pied de page, JSON-LD, llms.txt).
// L'adresse canonique vient de APP_URL (.env). Libellés du menu : clés des catalogues lang/fr.php et lang/br.php.
// Source des faits : mode d'emploi du projet (Contexte, Hébergement).
return [
    'nom' => 'Sécurité Sociale de l\'Alimentation du Pays de Morlaix',
    'nom_court' => 'SSA Pays de Morlaix',
    'description' => 'Une expérimentation citoyenne de caisse alimentaire au Pays de Morlaix : cotisation choisie librement, allocation en monnaie locale, lieux choisis ensemble.',
    'email' => 'ssapaysdemorlaix@mailo.com',
    'email_candidatures' => 'candidature@mailo.com',
    'telephone' => '06 15 06 82 08',
    'adresse' => [
        'lieu' => 'Le Buzuk',
        'rue' => '39 bellevue de la Madeleine',
        'code_postal' => '29600',
        'ville' => 'Morlaix',
    ],
    'contact_structure' => 'ULAMIR-CPIE',
    // Éditeur légal : à confirmer par Géraldine Gabillet (mode d'emploi, « À fournir »).
    'editeur' => [
        'nom' => 'Union locale animation canton Lanmeur (ULAMIR-CPIE Pays de Morlaix-Trégor)',
        'siret' => '300 796 349 00011',
        'adresse' => 'salle Steredenn, 4 rue du 19 Mars 1962, 29620 Lanmeur',
        'directrice_publication' => 'Géraldine Gabillet',
    ],
    'hebergeur' => [
        'nom' => 'La Capsule, chantier d\'insertion numérique de l\'ULAMIR-CPIE',
        'adresse' => 'centre AFPA de Morlaix, 39 Bellevue de la Madeleine, 29600 Morlaix',
        'telephone' => '07 86 44 77 52',
        'email' => 'contact@lacapsule.org',
    ],
    'langue' => 'fr',
    'menu' => [
        '/' => 'menu.accueil',
        '/projet/' => 'menu.projet',
        '/collectif/' => 'menu.collectif',
        '/lieux-conventionnes/' => 'menu.lieux',
        '/actualites/' => 'menu.actualites',
        '/agenda/' => 'menu.agenda',
        '/galerie/' => 'menu.galerie',
        '/contact/' => 'menu.contact',
    ],
    'legal' => [
        '/mentions-legales/' => 'pied.mentions',
        '/confidentialite/' => 'pied.confidentialite',
        '/accessibilite/' => 'pied.accessibilite',
        '/plan-du-site/' => 'pied.plan_du_site',
    ],
    // N'y ajouter une page qu'une fois sa route en place : sitemap.xml ne doit annoncer aucune adresse en 404.
    'pages' => [
        '/' => ['titre' => 'Accueil', 'description' => ''],
    ],
];
