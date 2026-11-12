<?php
// Famille « page de contenu » (service, à propos, fiche, page légale) : colonne de lecture centrée
// de 65 caractères, en-tête et corps sur le même axe (rule web-design). Mêmes props que Base.
layout('Base', $props, function () use ($contenu) { ?>
    <article class="lecture">
<?php $contenu(); ?>
    </article>
<?php });
