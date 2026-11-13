<?php
/** Qui sommes-nous (maquette docs/maquettes/collectif.md). @var array<string, string> $t @var string $path */
partial('site/haut', ['chemin' => $path, 'titre' => $t['titre'], 'chapo' => $t['chapo']]);
?>
<section class="section" aria-labelledby="structures-titre">
  <div class="container">
    <div class="section__head"><h2 id="structures-titre"><?= e($t['structures_titre']) ?></h2></div>
    <ul class="cards">
      <?php foreach ([1, 2, 3, 4] as $n): ?>
      <li class="card"><h3 class="card__title"><?= e($t['structure_' . $n . '_nom']) ?></h3><p><?= e($t['structure_' . $n . '_texte']) ?></p></li>
      <?php endforeach ?>
    </ul>
  </div>
</section>

<section class="section section--fond" aria-labelledby="objectif-titre">
  <div class="container prose">
    <h2 id="objectif-titre"><?= e($t['objectif_titre']) ?></h2>
    <p><?= e($t['objectif_texte']) ?></p>
  </div>
</section>

<section class="section cta" aria-labelledby="contact-titre">
  <div class="container">
    <h2 id="contact-titre"><?= e(t('contact.une_question')) ?></h2>
    <p><a class="button" href="<?= e(lurl('/contact/')) ?>"><?= e(t('contact.nous_contacter')) ?></a></p>
  </div>
</section>
