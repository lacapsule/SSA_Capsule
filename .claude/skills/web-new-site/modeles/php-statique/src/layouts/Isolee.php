<?php
// Famille « page isolée » (404, merci, confirmation, plan du site) : bloc étroit centré (rule web-design, « Centrage »).
layout('Base', $props, function () use ($contenu) { ?>
    <div class="isolee">
<?php $contenu(); ?>
    </div>
<?php });
