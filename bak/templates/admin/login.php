<?php

/** @var array<string, string> $str */
?>

<h1><?= secure_html($title ?? $str['login_title']) ?></h1>



<section class="login">
    <form method="POST">
        <?= \CapsuleLib\Security\CsrfTokenManager::insertInput(); ?>

        <?php if (!empty($error)): ?>
            <p style="color:red"><?= secure_html($error) ?></p>
        <?php endif; ?>

        <label for="username"><?= secure_html($str['login_username']) ?></label>
        <input id="username" name="username" required />

        <label for="password"><?= secure_html($str['login_password']) ?></label>
        <input id="password" name="password" type="password" required />

        <button type="submit"><?= secure_html($str['login_submit']) ?></button>
    </form>
</section>