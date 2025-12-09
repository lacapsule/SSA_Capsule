<header id="header">
    <a href="/" class="logo-link">
        <img src="/assets/img/logo.svg" alt="<?= secure_html($str['nav_title']) ?>" class="logo">
    </a>

    <div class="hamburger">
        <span class="line"></span><span class="line"></span><span class="line"></span><span class="line"></span>
    </div>

    <nav class="navbar">
        <ul>
            <li><a href="/"><?= secure_html($str['nav_home']) ?></a></li>
            <li><a href="/#about"><?= secure_html($str['nav_apropos']) ?></a></li>
            <li><a href="/#news"><?= secure_html($str['nav_actualites']) ?></a></li>
            <li><a href="/#agenda"><?= secure_html($str['nav_agenda']) ?></a></li>
            <li><a href="/projet"><?= secure_html($str['nav_project']) ?></a></li>
            <li><a href="/galerie"><?= secure_html($str['nav_galerie']) ?></a></li>
            <li><a href="/#contact"><?= secure_html($str['nav_contact']) ?></a></li>

            <li>
                <form method="get" action="">
                    <select name="lang" id="lang-switch" onchange="this.form.submit()">
                        <option value="fr" <?= ($_SESSION['lang'] ?? 'fr') === 'fr' ? 'selected' : '' ?>>
                            <?= secure_html($str['lang_fr']) ?>
                        </option>
                        <option value="br" <?= ($_SESSION['lang'] ?? 'fr') === 'br' ? 'selected' : '' ?>>
                            <?= secure_html($str['lang_br']) ?>
                        </option>
                    </select>
                </form>
            </li>

            <?php if (\CapsuleLib\Security\CurrentUserProvider::isAuthenticated()): ?>
                <li><a class="icons" href="/dashboard/home">
                        <img src="/assets/icons/dashboard.svg" alt="Dashboard icon">
                    </a></li>
                <li><a class="icons" href="/logout">
                        <img src="/assets/icons/logout.svg" alt="Logout icon">
                    </a></li>
            <?php else: ?>
                <li><a class="icons" href="/login">
                        <img src="/assets/icons/login.svg" alt="Login icon">
                    </a></li>
            <?php endif; ?>
        </ul>
    </nav>
</header>
