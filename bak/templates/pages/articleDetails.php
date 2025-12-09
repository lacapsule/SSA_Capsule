<section class="details">
    <div style="margin-top: 100px;">
        <div class="heading">
            <h1><?= secure_html($article->titre) ?></h1>
            <p class="date">Date : <?= date('d/m/Y', strtotime(secure_html($article->date_article))) ?></p>
        </div>
        <p>Article rédigé par : <?= secure_html($article->author) ?></p>
        <p class="lieu">Lieu : <?= secure_html($article->lieu) ?></p>
        <div class="separator"></div>
        <h3><?= secure_html($article->resume) ?></h3>
        <img src="<?= $article->image ? secure_html($article->image) : '/assets/img/banner.webp' ?>" alt="Banner">
        <div class="separator"></div>
        <p><?= secure_html($article->description) ?></p>
    </div>
</section>