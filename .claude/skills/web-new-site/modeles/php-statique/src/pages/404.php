<?php
layout('Isolee', ['titre' => 'Page introuvable', 'description' => 'Cette page n\'existe pas ou a été déplacée.', 'noindex' => true], function () { ?>
    <h1>Page introuvable</h1>
    <p>Cette adresse ne correspond à aucune page du site.</p>
    <p><a href="/">Retour à l'accueil</a></p>
<?php if (data('navigation')['principale']): ?>
    <nav aria-label="Pages principales">
      <ul class="navigation">
<?php foreach (data('navigation')['principale'] as $lien): ?>
        <li><a href="<?= e($lien['chemin']) ?>"><?= e($lien['titre']) ?></a></li>
<?php endforeach ?>
      </ul>
    </nav>
<?php endif ?>
<?php });
