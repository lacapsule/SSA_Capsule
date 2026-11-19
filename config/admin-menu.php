<?php
declare(strict_types=1);

// Menu du tableau de bord, au format de la coquille de référence (gabarits/admin-coquille, templates/admin/coquille.php) :
// rubriques (groupe, '' pour la première sans titre) et entrées dans l'ordre d'affichage.
// Clés d'une entrée : url (telle que la route du module), libelle, icone (clé de $icones dans coquille.php), court (libellé des onglets
// du bas), bas (true : aussi dans les onglets du bas du téléphone, 4 au plus), compteur (nombre à traiter),
// role (facultatif : 'admin' réserve l'entrée aux administrateurs).
// Une entrée dont le module n'est pas installé se retire ici. Rubriques du mode d'emploi (actualités, agenda, galerie,
// partenaires et financeurs, lieux conventionnés) : ajoutées avec leur module (phase 2).
return [
    ['groupe' => '', 'entrees' => [
        ['url' => '/admin/', 'libelle' => 'Tableau de bord', 'court' => 'Accueil', 'icone' => 'accueil', 'bas' => true],
    ]],
    ['groupe' => 'Réglages', 'entrees' => [
        ['url' => '/admin/securite/', 'libelle' => 'Sécurité du compte', 'court' => 'Sécurité', 'icone' => 'cle', 'bas' => true],
        ['url' => '/admin/comptes/', 'libelle' => 'Comptes', 'icone' => 'comptes', 'role' => 'admin'],
        ['url' => '/admin/journal/', 'libelle' => 'Journal', 'icone' => 'journal', 'role' => 'admin'],
    ]],
];
