<?php
declare(strict_types=1);

// Rubriques simples du tableau de bord (modules/crud-declaratif) : une ligne de table = une fiche. Une entrée ici = une
// rubrique complète (liste, ajout, modification, suppression, ordre), routes comprises. Voir NOTES.md du module.
// Types de champ : text, email, url, tel, textarea, date, number, checkbox, select, image, pdf, address.
// Tables du site (migrations 300 à 302) : ce sont elles que lisent les pages publiques.
return [
    'partenaires' => [
        'table' => 'partenaires',
        'label' => 'Partenaires et financeurs',
        'singular' => 'structure',
        'feminine' => true,
        'intro' => 'Les structures porteuses et les financeurs affichés sur l\'accueil et la page du projet, dans l\'ordre choisi ici. Utilisez le logo officiel de chaque structure.',
        'order' => 'groupe DESC, position, nom',
        'partage' => true,
        // Ordre par flèches, propre à chaque groupe (partenaires d'un côté, financeurs de l'autre).
        'sortable' => ['groupe'],
        'list_group' => 'groupe',
        'columns' => ['nom' => 'Nom', 'logo' => 'Logo', 'url' => 'Site web'],
        'fields' => [
            'nom' => ['label' => 'Nom', 'type' => 'text', 'required' => true, 'max' => 160],
            'groupe' => ['label' => 'Rôle', 'type' => 'select', 'options' => ['partenaire' => 'Structure porteuse', 'financeur' => 'Financeur'], 'required' => true],
            'description' => ['label' => 'Rôle en une ligne', 'type' => 'text', 'max' => 300, 'hint' => 'Structures porteuses : montré sous le nom, par exemple « Maison des associations du Pays de Morlaix. » Les financeurs n\'en ont pas besoin.'],
            'url' => ['label' => 'Site web', 'type' => 'url', 'hint' => 'Adresse complète, par exemple https://exemple.org. Le logo y mène depuis le site.'],
            'logo' => ['label' => 'Logo', 'type' => 'image', 'folder' => 'partenaires', 'widths' => [480], 'height' => 160, 'required' => true, 'hint' => 'PNG, JPG ou WebP, 15 Mo au plus. Converti en WebP, 160 pixels de haut au plus.'],
        ],
    ],

    'lieux' => [
        'table' => 'lieux_conventionnes',
        'label' => 'Lieux conventionnés',
        'singular' => 'lieu',
        'plural' => 'lieux',
        'intro' => 'Les lieux de la page « Où dépenser ses Buzuk SSA » et de sa carte. À l\'enregistrement, l\'adresse est placée sur la carte par l\'IGN ; vérifiez la position annoncée.',
        'order' => 'ville, nom',
        'partage' => true,
        'tabs' => 'statut',
        'timestamps' => true,
        'created' => true,
        // Export tableur de tous les lieux (App\Admin\Exports, ajout du projet SSA).
        'export' => '/admin/lieux/export',
        // Rue, code postal et commune réunis pour le géocodage (une rue seule se place mal) ; centre : Morlaix.
        'geocode' => ['field' => ['adresse', 'code_postal', 'ville'], 'centre' => [48.577, -3.828]],
        'columns' => ['nom' => 'Nom', 'ville' => 'Commune', 'statut' => 'Statut', 'is_published' => 'En ligne'],
        'fields' => [
            'nom' => ['label' => 'Nom', 'type' => 'text', 'required' => true, 'max' => 160],
            'statut' => ['label' => 'Statut', 'type' => 'select', 'required' => true, 'options' => ['ok' => 'Conventionné', 'attente' => 'En attente', 'refus' => 'Refusé', 'sans' => 'Sans conventionnement']],
            // Adresse facultative : 25 lieux de la carte du collectif n'ont qu'une position ; la fiche s'enregistre sans
            // adresse et garde sa position (geocode ne touche à rien tant que l'adresse ne change pas).
            'adresse' => ['label' => 'Adresse', 'type' => 'address', 'max' => 300, 'hint' => 'Numéro et rue, ou lieu-dit : choisissez une suggestion, le code postal et la commune se remplissent.', 'remplit' => ['postcode' => 'code_postal', 'city' => 'ville']],
            'code_postal' => ['label' => 'Code postal', 'type' => 'text', 'max' => 10],
            'ville' => ['label' => 'Commune', 'type' => 'text', 'max' => 120],
            'description' => ['label' => 'Produits et informations', 'type' => 'textarea', 'hint' => 'Ce que l\'on y trouve, en quelques mots. Une adresse web (boutique en ligne) y devient un lien.'],
            'horaires' => ['label' => 'Horaires', 'type' => 'textarea'],
            'is_published' => ['label' => 'Afficher sur le site', 'type' => 'checkbox', 'default' => 1],
        ],
    ],
];
