<?php
declare(strict_types=1);

/*
 * Langues du site (modules/multilingue). Inclus par config/app.php : 'langues' => require __DIR__ . '/langues.php'.
 * La langue par défaut est servie à la racine (/a-propos/), les autres sous leur préfixe (/en/about/).
 *
 * langues : code (minuscules, celui de l'URL) => réglages
 *   nom       nom de la langue dans sa propre langue (sélecteur : jamais de drapeau)
 *   locale    og:locale (fr_FR, en_GB)
 *   hreflang  valeur de hreflang si elle diffère du code (en-GB pour viser un pays) ; absent : le code
 *   dir       ltr ou rtl (arabe, hébreu)
 * chemins : adresses traduites, facultatif. Chemin de la langue par défaut => [code => chemin traduit, sans préfixe].
 *   Une clé finie par « / » vaut aussi pour tout ce qu'elle contient : '/actualites/' => ['en' => '/news/']
 *   donne /en/news/12/ pour /actualites/12/. Sans entrée, le chemin reste le même sous le préfixe (/en/contact/).
 * proposition : bandeau discret « Cette page existe en English » selon la langue du navigateur, sans redirection.
 */
return [
    'defaut' => 'fr',
    'langues' => [
        'fr' => ['nom' => 'Français', 'locale' => 'fr_FR', 'dir' => 'ltr'],
        'br' => ['nom' => 'Brezhoneg', 'locale' => 'br_FR', 'dir' => 'ltr'],
    ],
    // Mode d'emploi : chaque page bretonne a la même adresse que la française, précédée de /br/.
    'chemins' => [],
    'proposition' => false,
    // Champ qui décide qu'un contenu existe dans une langue (sa traduction non vide) : hreflang, sitemap, sélecteur.
    'champ_requis' => 'titre',
];
