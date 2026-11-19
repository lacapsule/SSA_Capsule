<?php
declare(strict_types=1);

/*
 * Expiration de session (composants/minuteur-session), lu par config('session_minuteur.cle').
 * Dans config/app.php : 'session_minuteur' => require __DIR__ . '/session-minuteur.php',
 * Durées en secondes. session.lifetime du socle (durée de vie des fichiers de session) doit être au moins égale à 'inactivite'.
 */
return [
    // Fermeture après N secondes sans requête de la personne, et au plus tard N secondes après la connexion.
    'inactivite' => 1800,
    'absolue' => 43200,
    // Avertissement dans le navigateur N secondes avant (WCAG 2.2.1 : 20 s au moins pour réagir).
    'avertir' => 120,

    // Clé de session présente quand quelqu'un est connecté : 'admin_id' (modules/authentification-admin), 'membre_id'...
    'cle_connexion' => 'admin_id',
    // Horodatages en session. Avec modules/authentification-admin : 'admin_seen' et 'admin_since' (ses propres clés),
    // pour que les deux contrôles comptent la même activité.
    'cle_activite' => 'admin_seen',
    'cle_debut' => 'admin_since',

    // Page de connexion (chemin local) ; la page quittée lui est passée en ?retour=.
    'connexion' => '/admin/connexion/',
    // Déconnexion (POST, même jeton CSRF), proposée dans l'avertissement ; '' : pas de bouton.
    'deconnexion' => '/admin/deconnexion/',

    // Requêtes qui ne comptent pas comme une activité (état interrogé par le script, appels de fond).
    'passifs' => ['/session/etat', '/session/expiree'],

    // Message d'expiration : 'flash' (Session::flash, affiché par les gabarits qui montrent les messages, comme
    // gabarits/tableau-de-bord-php) ou 'partiel' (partial('session/expiration') dans la page de connexion).
    'avis' => 'flash',

    // Vrai : une frappe ou un clic dans la page prolonge la session en silence (au plus une fois toutes les 5 minutes).
    'prolonger_sur_activite' => false,
];
