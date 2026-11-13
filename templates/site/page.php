<?php
/**
 * Page simple : en-tête (fil d'Ariane, titre, chapeau) puis le corps fourni par le contrôleur ($body, HTML déjà échappé).
 * Pour des pages faites de sections Markdown éditables : modules/pages-sections-markdown remplace ce fichier.
 * @var array<string, string> $meta @var string $path @var string $body
 */
?>
<div class="page-head">
  <div class="page-head__inner container">
    <?php if ($path !== '/'): ?>
    <nav class="breadcrumb" aria-label="Fil d'Ariane">
      <ol>
        <li><a href="/">Accueil</a></li>
        <li aria-current="page"><?= e($meta['h1']) ?></li>
      </ol>
    </nav>
    <?php endif ?>
    <h1><?= e($meta['h1']) ?></h1>
    <?php if (($meta['intro'] ?? '') !== ''): ?><p class="page-head__intro"><?= e($meta['intro']) ?></p><?php endif ?>
  </div>
</div>
<section class="section">
  <div class="container prose">
    <?= $body ?>
  </div>
</section>
