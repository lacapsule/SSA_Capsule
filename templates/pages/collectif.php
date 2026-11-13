<?php
/** Qui sommes-nous (maquette docs/maquettes/collectif.md). @var array<string, string> $t @var string $path */
partial('site/haut', ['chemin' => $path, 'titre' => $t['titre'], 'chapo' => $t['chapo']]);
?>
<section class="section" aria-labelledby="structures-titre">
  <div class="container duo">
    <span class="portail" aria-hidden="true"><span class="portail__calque"><img src="/assets/img/illustration-collectif.svg" alt="" width="240" height="324" loading="lazy" decoding="async"></span></span>
    <div class="prose">
      <h2 id="structures-titre"><?= e($t['structures_titre']) ?></h2>
      <ul class="puces">
        <?php foreach ([1, 2, 3, 4] as $n): ?>
        <li><strong><?= e($t['structure_' . $n . '_nom']) ?></strong> : <?= e($t['structure_' . $n . '_texte']) ?></li>
        <?php endforeach ?>
      </ul>
    </div>
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
