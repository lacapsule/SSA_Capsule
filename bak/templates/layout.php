<?php

/** @var array<string, string> $str */
?>

<!DOCTYPE html>
<html lang="<?= secure_attr($str['lang']) ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= secure_attr($str['meta_description']) ?>">
    <meta name="keywords" content="<?= secure_attr($st['meta_keywords']) ?>">
    <meta name="author" content="<?= secure_attr($str['meta_author']) ?>">

    <title><?= secure_html($str['page_title']) ?></title>

    <link rel="stylesheet" href="/assets/css/styles.css">
    <link rel="icon" type="image/png" href="/assets/img/logoSSA.png">
</head>

<body>
    <?php if (!empty($showHeader)): ?>
        <?php require dirname(__DIR__) . '/templates/partials/header.php'; ?>
    <?php endif; ?>
    <main>
        <?= $viewContent ?>
    </main>

    <?php if (!empty($showFooter)): ?>
        <?php require dirname(__DIR__) . '/templates/partials/footer.php'; ?>
    <?php endif; ?>
    <script src="/assets/js/script.js" defer></script>
</body>

</html>