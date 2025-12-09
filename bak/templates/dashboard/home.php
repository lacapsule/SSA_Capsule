<?php ?>

<section class="admin-dashboard">
    <aside>
        <h2>Dashboard</h2>
        <ul>
            <?php foreach ($links as $link): ?>
                <li>
                    <a href="<?= htmlspecialchars($link['url']) ?>">
                        <img src="/assets/icons/<?= htmlspecialchars($link['icon']) ?>.svg" alt="">
                        <?= htmlspecialchars($link['title']) ?>
                    </a>
                </li>
            <?php endforeach ?>
        </ul>
    </aside>
    <div class="dashboard-content">
        <?= $dashboardContent ?>
    </div>
</section>