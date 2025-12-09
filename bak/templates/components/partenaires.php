<?php

/** @var array<string, string> $str */

$partenaires = [
    ['name' => 'BUZUK', 'role' => 'partenaire', 'url' => 'https://buzuk.bzh/', 'logo' => '/assets/img/buzuk.webp'],
    ['name' => 'Région Bretagne', 'role' => 'financeur', 'url' => 'https://www.bretagne.bzh/', 'logo' => '/assets/img/bretagne.webp'],
    ['name' => 'ULAMIR-CPIE', 'role' => 'partenaire', 'url' => 'https://ulamir-cpie.bzh/', 'logo' => '/assets/img/ulamircpie.webp'],
    ['name' => 'Pôle ESS Pays de Morlaix', 'role' => 'partenaire', 'url' => 'https://www.adess29.fr/faire-reseau/le-pole-du-pays-de-morlaix/', 'logo' => '/assets/img/ess.webp'],
    ['name' => 'RESAM', 'role' => 'partenaire', 'url' => 'https://www.resam.net/', 'logo' => '/assets/img/resam.webp'],
    ['name' => 'Leader financement Européen', 'role' => 'financeur', 'url' => 'https://leaderfrance.fr/le-programme-leader/', 'logo' => '/assets/img/feader.webp'],
];

// Séparer partenaires et financeurs
$onlyPartenaires = array_filter($partenaires, fn($p) => $p['role'] === 'partenaire' || $p['role'] === '');
$onlyFinanceurs  = array_filter($partenaires, fn($p) => $p['role'] === 'financeur');

?>

<section class="partenaires">
    <h2><?= secure_html($str['partners_title']) ?></h2>

    <div class="icons partners">
        <h3>Partenaires</h3>
        <div class="logos">

            <?php foreach ($onlyPartenaires as $p): ?>
                <a href="<?= secure_url($p['url']) ?>" target="_blank" rel="noreferrer noopener">
                    <img src="<?= secure_attr($p['logo']) ?>" alt="<?= secure_attr($p['name']) ?>">
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="separator"></div>

    <div class="icons financeurs">
        <h3>Financeurs</h3>
        <div class="logos">

            <?php foreach ($onlyFinanceurs as $p): ?>
                <a href="<?= secure_url($p['url']) ?>" target="_blank" rel="noreferrer noopener">
                    <img src="<?= secure_attr($p['logo']) ?>" alt="<?= secure_attr($p['name']) ?>">
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>