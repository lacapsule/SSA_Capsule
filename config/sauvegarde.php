<?php
declare(strict_types=1);

/*
 * Réglages des sauvegardes chiffrées (modules/sauvegarde-chiffree), inclus par config/app.php :
 *   'sauvegarde' => require __DIR__ . '/sauvegarde.php',
 * La clé n'est JAMAIS ici : BACKUP_KEY dans le .env (et une copie hors du serveur).
 */
return [
    // Base SQLite à sauvegarder. Vide : celle de DB_DSN (sqlite:...). MySQL et MariaDB ne sont pas pris en charge.
    'base' => '',
    // Dossier des sauvegardes, hors de la racine web (droits 0700, fichiers 0600).
    'dossier' => ROOT . '/data/sauvegardes',
    // Début du nom des fichiers : <prefixe>-AAAAMMJJ-HHMMSS-<type>.sqlite.gz.enc
    'prefixe' => 'sauvegarde',
    // Rétention : tout ce qui a moins de 30 jours, plus la première sauvegarde de chacun des 12 derniers mois.
    'retention_jours' => 30,
    'retention_mois' => 12,
    // Tables sans lesquelles une sauvegarde n'est pas restaurable (vérification).
    'tables_requises' => ['schema_migrations', 'admins'],
    // Tables dont la vérification compare le nombre de lignes avec la base actuelle (ignorées si absentes des deux).
    // Contenus du site comptés dans chaque sauvegarde (vérification : une sauvegarde vide se voit).
    'tables_comptees' => ['admins', 'admin_log', 'articles', 'events', 'galerie_photos', 'partenaires', 'lieux_conventionnes'],
    // Alerte de l'écran d'administration et de la tâche planifiée.
    'alerte_sauvegarde_jours' => 2,
    'alerte_verification_jours' => 8,
    // Écran d'administration : bouton « Lancer maintenant » et « Vérifier » (administrateurs seulement).
    'web_actions' => true,
    // Restauration depuis le navigateur (administrateurs, saisie de RESTAURER, sauvegarde de sécurité avant).
    // Désactivée par défaut : à n'activer que sur un hébergement sans accès SSH. Jamais de téléchargement par le web.
    'web_restauration' => false,
    // null : chiffrement exigé partout sauf APP_ENV=local. true : exigé partout.
    'exiger_chiffrement' => null,
];
