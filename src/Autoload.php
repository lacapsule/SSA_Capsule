<?php

declare(strict_types=1);

namespace Capsule;

/**
 * Table de correspondance entre namespaces racines et répertoires physiques.
 *
 * Cette constante associe chaque namespace racine à un dossier racine sur le système de fichiers.
 * Elle est utilisée par l’autoloader PSR-4 simplifié pour localiser les fichiers sources.
 *
 * @var array<string, string> Clef : namespace racine, valeur : chemin relatif au dossier parent
 *
 * Exemple d’usage :
 * - 'Capsule\Controller\X' → 'src/Controller/X.php'
 * - 'App\Repository\Y' → 'app/Repository/Y.php'
 */
const ALIASES = [
    'Capsule' => 'src',
    'App' => 'app',
];

/**
 * Autoloader PSR-4 simplifié.
 *
 * Charge automatiquement les classes PHP en mappant les namespaces sur le système de fichiers.
 * Ne gère que les namespaces commençant par une clé définie dans ALIASES.
 *
 * Fonctionnement :
 * 1. Explose le namespace complet en segments (ex: Capsule\Controller\ViewController)
 * 2. Remplace le premier segment par son alias dossier (ex: src)
 * 3. Construit le chemin absolu vers le fichier PHP (ex: /path/to/src/Controller/ViewController.php)
 * 4. Vérifie que le fichier existe, sinon lève une exception
 * 5. Inclut le fichier PHP
 *
 * @param string $class Namespace complet de la classe demandée
 * @throws \Exception Si le namespace racine est invalide ou que le fichier n’existe pas
 */
spl_autoload_register(function (string $class): void {
    $namespaceParts = explode('\\', $class);
    $rootNamespace = $namespaceParts[0];

    if (array_key_exists($rootNamespace, ALIASES)) {
        $namespaceParts[0] = ALIASES[$rootNamespace];
    } else {
        throw new \Exception(
            "Namespace « $rootNamespace » invalide. "
                . 'Un namespace doit commencer par : « ' . implode(' », « ', array_keys(ALIASES)) . ' »'
        );
    }

    $filepath = dirname(__DIR__) . '/' . implode('/', $namespaceParts) . '.php';

    if (!file_exists($filepath)) {
        throw new \Exception(
            "Fichier introuvable : « $filepath » pour la classe « $class ». "
                . 'Vérifie le nom de fichier, la casse et le namespace.'
        );
    }

    require $filepath;
});
