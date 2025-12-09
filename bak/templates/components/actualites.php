<?php

/** @var array<string, string> $str */
/** @var array<int, array<string, string>> $articles */
$articles = [
    [
        'titre'    => "lancement de l'expérimentation SSA du Pays de Morlaix.",
        'resume'  => "Samedi 18 octobre de 11h à 15h30 : lancement de l'expérimentation SSA du Pays de Morlaix avec les personnes sélectionnées.",
        'description' => "Au programme de l'interconnaissance, des ateliers d'échanges et un repas partagé ! lieu : espace du binigou St Martin des Champs.",
        'category' => 'sante',
        'image'    => '/assets/img/flyer_SSA_Pays_Morlaix.png',
        'link'     => '/article/11',
        'createdAt' => '2024-06-15',
        'date_article' => '2025-06-24',
        'id' => '11',
        'author' => 'admin'
    ],
    [
        'titre'    => 'Lancement du recrutement !',
        'resume'  => 'La campagne de recrutement des 100 personnes qui participeront à la caisse commune est lancé jusqu’au 20 septembre.',
        'description' => ' C’est ouvert à toutes et tous, sans conditions de ressources. L’objectif est d’avoir un panel de candidat.e.s le plus représentatif possible de la population du territoire du pays de Morlaix, en terme de niveau de vie et de composition du foyer. Si les candidatures sont trop nombreuses, un tirage au sort sera effectué par catégorie. Le dossier de candidature en ligne est à télécharger dans la section "Projet SSA".',
        'category' => 'sante',
        'image'    => '/assets/img/gallery/image_78.webp',
        'link'     => '/article/1',
        'createdAt' => '2024-06-15',
        'date_article' => '2025-06-24',
        'id' => '8',
        'author' => 'admin'
    ],
    [
        'titre'    => 'Stand sur les marchés de l’été.',
        'resume'  => 'Fin juin et juillet 2025, le collectif est intervenu sur les marchés du territoire.',
        'description' => 'Fin juin et juillet 2025, le collectif est intervenu sur les marchés du territoire : Carantec, Plougasnou, Lanmeur, ferme du Poder à Plouégat Guerrand, ferme du Troglo à Plouézoc’h, marché de Ty Grean à Plounéour Menez. Les stands ont permis de rencontrer de futurs participants et des nouveaux citoyens. D’autres interventions sur les marchés sont prévues à Saint Pol de Léon, Plouescat, Landivisiau...',
        'category' => 'environnement',
        'image'    => '/assets/img/banner.webp',
        'link'     => 'actualites.html',
        'createdAt' => '2024-06-15',
        'date_article' => '2025-06-22',
        'id' => '9',
        'author' => 'admin'
    ],
    [
        'titre'    => 'Conférence gesticulée - Mathieu Dalmais.',
        'resume'  => 'Dimanche 6 juillet, Mathieu Dalmais a présenté sa conférence gesticulée devant une quarantaine de personnes au centre social Ti an oll.',
        'description' => 'Dimanche 6 juillet, Mathieu Dalmais a présenté sa conférence gesticulée devant une quarantaine de personnes au centre social Ti an oll. Les participants ont apprécié la qualité de l’intervention. « On ne voit pas passer les 2heures » Les échanges et questions à la fin de la conférence ont été nombreux et très précis. Une belle soirée instructive et conviviale ! Le collectif pour une SSA en Pays de Morlaix a pu présenter l’avancement de son expérimentation. Les échanges se sont poursuivis avec un apéro convivial.',
        'category' => 'environnement',
        'image'    => '/assets/img/banner.webp',
        'link'     => 'actualites.html',
        'createdAt' => '2024-06-15',
        'date_article' => '2025-07-06',
        'id' => '10',
        'author' => 'admin'
    ],
];
?>

<section id="news" class="news">
    <h2><?= secure_html($str['news_title']) ?></h2>

    <!-- <div class="filters">
        <button class="filter-btn" data-filter="all"><?= secure_html($str['news_filter_all']) ?></button>
        <button class="filter-btn" data-filter="sante"><?= secure_html($str['news_filter_sante']) ?></button>
        <button class="filter-btn" data-filter="environnement"><?= secure_html($str['news_filter_env']) ?></button>
        <button class="filter-btn" data-filter="mobilisation"><?= secure_html($str['news_filter_mob']) ?></button>
    </div> -->

    <div class="news-grid">
        <?php foreach ($articles as $article): ?>
            <article class="news-item" data-category="<?= secure_html($article['category']) ?>">
                <h3><?= secure_html($article['titre']) ?></h3>
                <p><?= secure_html($article['resume']) ?></p>
                <img src="<?= $article['image'] ?>" alt="illustration article">
                <a href="/article/<?= $article['id'] ?>" class="read-more">
                    <?= secure_html($str['read_more']) ?>
                </a>
            </article>
        <?php endforeach; ?>
    </div>
</section>
