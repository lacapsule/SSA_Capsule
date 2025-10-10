<section class="details">
    <div class="heading">
        <h1><?= secure_html($article->titre) ?></h1>
        <p class="date">Date : <?= date('d/m/Y', strtotime(secure_html($article->date_article))) ?></p>
    </div>
    <p>Article rédigé par : <?= secure_html($article->author) ?></p>
    <p class="lieu">Lieu : <?= secure_html($article->lieu) ?></p>
    <div class="separator"></div>
    <h3><?= secure_html($article->resume) ?></h3>
    <p class="description"><?= secure_html($article->description) ?></p>
    <div class="separator"></div>
    <img src="<?= $article->image ? secure_html($article->image) : '/assets/img/banner.webp' ?>" alt="Banner">
</section>