<?php
/** Le projet (maquette docs/maquettes/projet.md). @var array<string, string> $t @var string $path */
partial('site/haut', ['chemin' => $path, 'titre' => $t['titre'], 'chapo' => $t['chapo']]);
?>
<section class="section" aria-labelledby="pourquoi-titre">
  <div class="container prose">
    <h2 id="pourquoi-titre"><?= e($t['pourquoi_titre']) ?></h2>
    <p><?= e($t['pourquoi_texte']) ?></p>
    <ul>
      <?php foreach (['but_1', 'but_2', 'but_3'] as $cle): ?><li><?= e($t[$cle]) ?></li><?php endforeach ?>
    </ul>
    <p><?= e($t['objectif']) ?></p>
  </div>
</section>

<section class="section section--etapes" aria-labelledby="concret-titre">
  <div class="container">
    <div class="section__head"><h2 id="concret-titre"><?= e($t['concret_titre']) ?></h2></div>
    <ol class="frise">
      <?php foreach (['concret_1', 'concret_2', 'concret_3'] as $cle): ?>
      <li class="frise__item"><span class="frise__dot" aria-hidden="true"></span><p><?= e($t[$cle]) ?></p></li>
      <?php endforeach ?>
    </ol>
  </div>
</section>

<section class="section" aria-labelledby="engagement-titre">
  <div class="container prose">
    <h2 id="engagement-titre"><?= e($t['engagement_titre']) ?></h2>
    <p><?= e($t['engagement_texte']) ?></p>
  </div>
</section>

<section class="section cta" aria-labelledby="contact-titre">
  <div class="container">
    <h2 id="contact-titre"><?= e($t['contact_titre']) ?></h2>
    <p>
      <a class="button" href="<?= e(lurl('/contact/')) ?>"><?= e(t('contact.nous_contacter')) ?></a>
      <a class="button button--ghost" href="<?= e(lurl('/lieux-conventionnes/')) ?>"><?= e(t('accueil.voir_lieux')) ?></a>
    </p>
  </div>
</section>
