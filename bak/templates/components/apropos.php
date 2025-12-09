<?php

/** @var array<string, string> $str */
?>

<div class="background">
    <section id="about" class="about">
        <div class="text">
            <h2><?= secure_html($str['about_title']) ?></h2>
            <h3><?= secure_html($str['about_subtitle']) ?></h3>
            <p><?= secure_html($str['about_intro']) ?></p>
            <p><strong><?= secure_html($str['about_axes']) ?></strong></p>
            <ul>
                <li><a href="https://ulamir-cpie.bzh/" target="_blank">l’ULAMiR CPIE</a> <?= secure_html($str['about_axes_1']) ?></li>
                <li><a href="https://www.resam.net/" target="_blank">le RESAM</a> <?= secure_html($str['about_axes_2']) ?></li>
                <li><a href="https://buzuk.bzh/" target="_blank">le Buzuk</a> <?= secure_html($str['about_axes_3']) ?></li>
                <li><a href="https://www.adess29.fr/faire-reseau/le-pole-du-pays-de-morlaix/" target="_blank">l'ADESS</a> <?= secure_html($str['about_axes_4']) ?></li>
            </ul>
            <p><strong><?= secure_html($str['about_goal_label']) ?></strong>
                <?= secure_html($str['about_goal']) ?>
            </p>
            <a href="/projet" class="btn">En savoir plus</a>
        </div>
        <div class="illustration">
            <img src="/assets/img/logo.svg" alt="<?= secure_attr($str['about_logo_alt']) ?>">
        </div>
    </section>
</div>