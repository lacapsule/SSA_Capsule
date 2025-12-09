<section class="form-section">
    <h2><?= secure_html($str['create_article_title']) ?></h2>
    <form method="post" action="/articles/create" enctype="multipart/form-data" autocomplete="off" class="article-form">
        <?= \CapsuleLib\Security\CsrfTokenManager::insertInput(); ?>

        <label for="titre"><?= secure_html($str['create_article_label_title']) ?></label>
        <input type="text" name="titre" id="titre" required maxlength="100" autofocus>

        <label for="description"><?= secure_html($str['create_article_label_desc']) ?></label>
        <textarea name="description" id="description" required rows="4" maxlength="1000"></textarea>

        <label for="resume"><?= secure_html($str['create_article_label_desc']) ?></label>
        <textarea name="resume" id="resume" required rows="4" maxlength="1000"></textarea>

        <label for="date_article"><?= secure_html($str['create_article_label_date']) ?></label>
        <input type="date" name="date_article" id="date_article" required>

        <label for="hours"><?= secure_html($str['create_article_label_time']) ?></label>
        <input type="time" name="hours" id="hours" required>

        <label for="lieu"><?= secure_html($str['create_article_label_place']) ?></label>
        <input type="text" name="lieu" id="lieu" required maxlength="100">

        <button type="submit"><?= secure_html($str['create_article_submit']) ?></button>
    </form>
</section>
