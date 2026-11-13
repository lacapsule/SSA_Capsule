<?php
declare(strict_types=1);

// Textes des pages fixes (accueil, projet, collectif), repris du site actuel (../SSA_Capsule1, templates/modules).
// Corrections : points de suspension de fin de puce retirés, futur passé au passé (« a débuté à l'automne 2025 »),
// bloc « On recrute ! » retiré (candidatures closes le 20 septembre 2025). Rien d'autre n'est ajouté.
// Chaque champ est traduit en breton par Gallek (objet « page », identifiant = chemin) ; la version bretonne vit
// dans la table traductions, jamais ici.
return [
    '/' => [
        'title' => 'Sécurité Sociale de l\'Alimentation du Pays de Morlaix',
        'titre' => 'Sécurité Sociale de l\'Alimentation, Pays de Morlaix',
        'devise' => 'Pour un droit fondamental et universel à une alimentation saine et durable.',
        'description' => 'Expérimentation citoyenne de Sécurité Sociale de l\'Alimentation au Pays de Morlaix : agenda, lieux conventionnés où dépenser ses Buzuk SSA, actualités.',
        'etapes_titre' => 'Comment ça marche',
        'etape_1' => 'Chaque participant·e choisit librement le montant de sa cotisation mensuelle.',
        'etape_2' => 'Chaque mois, il ou elle reçoit une allocation en monnaie locale alimentaire, le « Buzuk SSA ».',
        'etape_3' => 'L\'allocation se dépense dans des lieux validés démocratiquement par le groupe.',
        'collectif_soustitre' => 'Le groupe « Sécurité Sociale de l\'Alimentation » (SSA)',
        'collectif_titre' => 'Qui sommes-nous ?',
        'collectif_texte' => 'Le collectif pour une Sécurité Sociale de l\'Alimentation en Pays de Morlaix est composé d\'une trentaine de citoyennes et citoyens vivant sur le Pays de Morlaix. Il est accompagné par 4 structures associatives du Pays de Morlaix.',
        'contact_titre' => 'Une question sur la Sécurité Sociale de l\'Alimentation au Pays de Morlaix ?',
    ],
    '/projet/' => [
        'title' => 'Le projet : la caisse alimentaire du Pays de Morlaix',
        'titre' => 'Expérimentation d\'une caisse de l\'alimentation sur le Pays de Morlaix',
        'chapo' => 'Une base de connaissances collaborative sur la Sécurité Sociale de l\'Alimentation.',
        'description' => 'Comment fonctionne la caisse alimentaire du Pays de Morlaix : cotisation libre, allocation mensuelle en Buzuk SSA, lieux de dépense choisis ensemble.',
        'pourquoi_titre' => 'Un premier pas vers une Sécurité Sociale de l\'Alimentation',
        'pourquoi_texte' => 'La Sécurité Sociale de l\'Alimentation en Pays de Morlaix est une expérimentation portée par un collectif d\'associations et de citoyen·nes pour :',
        'but_1' => 'aller vers un droit à l\'alimentation pour toutes et tous et soutenir les paysan·nes ;',
        'but_2' => 'contribuer à construire un système alimentaire plus juste et plus durable ;',
        'but_3' => 'recréer du lien et reprendre du pouvoir d\'agir collectivement sur notre territoire.',
        'objectif' => 'C\'est une initiative locale qui s\'inspire d\'un projet de société : celui de mettre en place une sécurité sociale de l\'alimentation.',
        'concret_titre' => 'Plus concrètement',
        'concret_1' => 'Un parcours citoyen, qui a débuté à l\'automne 2025.',
        'concret_2' => 'Un budget collectif constitué par les cotisations des membres.',
        'concret_3' => 'Une allocation à dépenser chaque mois dans des lieux de distribution alimentaire.',
        // Schéma « Kezako » du site actuel : texte alternatif court, transcription fidèle affichée dessous.
        'schema_alt' => 'Schéma « Sécurité sociale de l\'alimentation, Kezako ? », transcrit ci-dessous.',
        'schema_lire' => 'Lire le schéma en texte',
        'schema_texte' => 'Les participant·es cotisent à hauteur de leurs moyens : c\'est le financement de la caisse locale. La caisse locale collecte les cotisations et les redistribue en Buzuks alimentaires : 1 personne = 150 Bzk, personne supplémentaire = 75 Bzk. Les Buzuks alimentaires se dépensent chez les producteur·ices et commerces du territoire conventionnés, au Pays de Morlaix (dessins d\'une épicerie locale, d\'une serre et de produits frais).',
        'engagement_titre' => 'L\'engagement des participant·es',
        'engagement_texte' => 'Chaque participant·e choisit librement le montant de sa cotisation mensuelle, puis reçoit une allocation en monnaie locale alimentaire, le « Buzuk SSA », à dépenser dans des lieux validés démocratiquement par le groupe. Chaque participant·e s\'engage à participer à une réunion et à un atelier collectif par mois. Des temps d\'échanges ont été organisés entre les participant·es pour choisir ensemble les lieux de dépense.',
        'contact_titre' => 'Besoin d\'information ?',
    ],
    '/collectif/' => [
        'titre' => 'Qui sommes-nous ?',
        'chapo' => 'Le collectif pour une Sécurité Sociale de l\'Alimentation en Pays de Morlaix est composé d\'une trentaine de citoyennes et citoyens vivant sur le Pays de Morlaix.',
        'description' => 'La Collective, une trentaine de citoyennes et citoyens, et les quatre associations qui portent la Sécurité Sociale de l\'Alimentation au Pays de Morlaix.',
        'structures_titre' => 'Les structures porteuses du projet',
        'structure_1_nom' => 'L\'ULAMIR-CPIE',
        'structure_1_texte' => 'Centre social et centre d\'éducation à l\'environnement.',
        'structure_2_nom' => 'Le Résam',
        'structure_2_texte' => 'Maison des associations du Pays de Morlaix.',
        'structure_3_nom' => 'Le Buzuk',
        'structure_3_texte' => 'Porteuse de la monnaie locale.',
        'structure_4_nom' => 'L\'Adess du Pays de Morlaix',
        'structure_4_texte' => 'Pôle ESS du Pays de Morlaix.',
        'objectif_titre' => 'Notre objectif',
        'objectif_texte' => 'Connecter les initiatives locales autour de l\'alimentation et renforcer les coopérations entre acteurs.',
    ],
    '/agenda/' => [
        'titre' => 'Agenda',
        'chapo' => 'Retrouvez nos événements à venir : réunions de La Collective, ateliers du parcours citoyen, rencontres ouvertes à toutes et tous.',
        'description' => 'Agenda de la Sécurité Sociale de l\'Alimentation du Pays de Morlaix : réunions mensuelles, ateliers du parcours citoyen et rencontres ouvertes au public.',
    ],
    '/actualites/' => [
        'titre' => 'Actualités',
        'chapo' => 'Les nouvelles de l\'expérimentation : temps forts du parcours citoyen, médias, vidéos.',
        'description' => 'Actualités de la Sécurité Sociale de l\'Alimentation du Pays de Morlaix : parcours citoyen, lancement de la caisse, vidéos et passages dans les médias.',
    ],
];
