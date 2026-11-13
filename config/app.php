<?php
declare(strict_types=1);

/*
 * Réglages du projet, lus par config('section.cle'). Aucun secret ici (versionné) : les secrets vont dans le .env.
 */
return [
    'app' => [
        'name' => 'SSA Pays de Morlaix',
        'timezone' => 'Europe/Paris',
        // Gabarit de page des écrans publics des modules (gabarits/site-public-php : 'site/layout').
        'layout' => 'layout',
    ],

    'security' => [
        // Domaine public, sans « www » ni protocole (ex. 'exemple.org'). HSTS n'est envoyé que sur cet hôte, en HTTPS.
        'canonical_host' => 'ssapaysdemorlaix.fr',
        'hsts_max_age' => 31536000,
        // true seulement si TOUS les sous-domaines sont en HTTPS : sinon ils deviennent injoignables.
        'hsts_subdomains' => false,

        // Mandataires inverses de confiance (IP ou CIDR). Vide : REMOTE_ADDR et HTTPS du serveur seuls font foi.
        'trusted_proxies' => [],

        // Sources ajoutées à la CSP de base ('self' partout, aucun script ni style en ligne).
        // Paiement hébergé : 'form-action' => ['https://paiement.exemple'] ; carte : 'img-src' => ['https://tuiles.exemple'].
        'csp' => [
            'form-action' => [],
            'connect-src' => [],
            'img-src' => [],
            'frame-src' => [],
        ],
        'permissions_policy' => 'camera=(), microphone=(), geolocation=(), payment=()',

        // Préfixes de chemins privés : noindex et aucune mise en cache (espace client, back-office, panier).
        'private_prefixes' => ['/admin'],
    ],

    // Module securite-formulaires (cookie de session, anti-robots).
    'session' => [
        'name' => 'app_session',
        'samesite' => 'Lax',
        'lifetime' => 7200,
    ],
    'forms' => [
        'honeypot' => 'site_web',
        'min_seconds' => 3,
        'max_age' => 7200,
    ],
    // Back-office (contrat des modules, NOTES.md). guard : classe qui implémente App\Admin\AdminGuard,
    // fournie par modules/authentification-admin (App\Admin\TwoFactorGuard). Vide tant qu'aucun module d'administration n'est installé.
    'admin' => [
        'base' => '/admin',
        'guard' => '',
        'layout' => 'admin/layout',
    ],

    // Réglages des modules : un fichier par module, inclus ici, lus par config('contact.destinataire').
    // 'contact' => require __DIR__ . '/contact.php',
];
