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
        // Position relevée auprès de l'IGN (config/lieux-evenements.tsv) : carte de la page Contact.
        'position' => 'afpa-morlaix',
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
    // Crédit du prestataire : copie de .claude/kouzia.json (le paquet de mise en ligne n'emporte pas .claude),
    // plus le complément propre au projet, repris du pied de page du site actuel avec son lien.
    'credit' => [
        'avant' => 'Design et développement : ',
        'lien' => ['texte' => 'Kouzia', 'url' => 'https://kouzia.com/'],
        'apres' => ', Alexandre Kouziaeff EI',
        'complement' => [', au sein de ', ['texte' => 'La Capsule', 'url' => 'https://lacapsule.org/']],
        'fin' => ' · site éco-conçu',
    ],
    'langue' => 'fr',
    'menu' => [
        '/' => 'menu.accueil',
        '/projet/' => 'menu.projet',
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
    // Photos de la galerie choisies pour l'accueil (maquette de la piste 2, livrable 04) et logos des structures
    // porteuses, dans l'ordre des structures de config/contenus.php. Un fichier absent est sauté.
    'accueil' => [
        'mosaique' => ['/media/galerie/20251124_144033_lot001.webp', '/media/galerie/20251124_144057_lot002.webp', '/media/galerie/20251124_144106_lot002.webp'],
        // Bande défilante : vignettes 400 × 500 tirées de la galerie (public/assets/img/bande/, 13 Ko en moyenne).
        'bande' => ['/assets/img/bande/photo-65.webp', '/assets/img/bande/photo-87.webp', '/assets/img/bande/photo-117.webp', '/assets/img/bande/photo-81.webp', '/assets/img/bande/photo-66.webp', '/assets/img/bande/photo-8.webp', '/assets/img/bande/photo-83.webp', '/assets/img/bande/photo-82.webp', '/assets/img/bande/photo-107.webp', '/assets/img/bande/photo-84.webp', '/assets/img/bande/photo-119.webp', '/assets/img/bande/photo-74.webp', '/assets/img/bande/photo-58.webp', '/assets/img/bande/photo-20.webp'],
        'logos_structures' => ['/media/partenaires/20251211-083116-1.webp', '/media/partenaires/resam.webp', '/media/partenaires/buzuk.webp', '/media/partenaires/ess.webp'],
    ],
    // Page Accessibilité (rule web-a11y) : niveau affiché seulement avec la date et la méthode d'un audit réel
    // (skill web-a11y-audit, à la recette) ; null masque le bloc. Jamais un niveau visé.
    // Vidéo montrée sur la page « Le projet » : celle de l'article (slug), source unique du fichier et de son image.
    // Nom lisible des sites cités dans les liens des articles (encadré « Liens ») ; sinon le domaine est affiché.
    'noms_sites' => [
        'youtube.com' => 'YouTube',
        'jne-asso.org' => 'JNE, journalistes-écrivains pour la nature et l\'écologie',
        'letelegramme.fr' => 'Le Télégramme',
        'radiofrance.fr' => 'Radio France',
        'eco-bretons.info' => 'Eco-Bretons',
        'pod.graffitiradio.fr' => 'Graffiti Radio',
        'umap.openstreetmap.fr' => 'uMap (OpenStreetMap)',
    ],
    'projet_video' => 'video-de-presentation-du-projet-ssa',
    'accessibilite' => [
        'referentiel' => 'WCAG 2.2',
        'niveau' => null,
        'date_audit' => null,
        'methode' => null,
    ],
    // N'y ajouter une page qu'une fois sa route en place : sitemap.xml ne doit annoncer aucune adresse en 404.
    'pages' => [
        '/' => ['titre' => 'Accueil', 'description' => ''],
        '/projet/' => ['titre' => 'Le projet', 'description' => ''],
        '/actualites/' => ['titre' => 'Actualités', 'description' => ''],
        '/agenda/' => ['titre' => 'Agenda', 'description' => ''],
        '/galerie/' => ['titre' => 'Galerie', 'description' => ''],
        '/contact/' => ['titre' => 'Contact', 'description' => ''],
        '/lieux-conventionnes/' => ['titre' => 'Lieux conventionnés', 'description' => ''],
        '/mentions-legales/' => ['titre' => 'Mentions légales', 'description' => ''],
        '/confidentialite/' => ['titre' => 'Confidentialité', 'description' => ''],
        '/accessibilite/' => ['titre' => 'Accessibilité', 'description' => ''],
        '/plan-du-site/' => ['titre' => 'Plan du site', 'description' => ''],
    ],
];
