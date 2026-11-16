<?php
/**
 * Le projet, piste 2 « modernisée » (maquette docs/maquettes/pistes/2-modernisee/projet.html) : titre et photo,
 * buts avec le personnage dans son rond, schéma « Kezako » en grand avec les trois étapes, engagement à côté de la
 * photo de groupe, appel au contact. @var array<string, string> $t @var string $path
 */
partial('site/haut', ['chemin' => $path, 'titre' => $t['titre'], 'chapo' => $t['chapo'], 'photo' => '/assets/img/bandeau-projet.webp']);
?>
<section class="section buts" aria-labelledby="pourquoi-titre">
  <div class="buts__inner container">
    <span class="portail portail--buts" aria-hidden="true"><span class="portail__calque"><img src="/assets/img/illustration-projet.svg" alt="" width="240" height="324" loading="lazy" decoding="async"></span></span>
    <div class="prose">
      <h2 id="pourquoi-titre"><?= e($t['pourquoi_titre']) ?></h2>
      <p><?= e($t['pourquoi_texte']) ?></p>
      <ul class="puces">
        <?php foreach (['but_1', 'but_2', 'but_3'] as $cle): ?><li><?= e($t[$cle]) ?></li><?php endforeach ?>
      </ul>
      <p><?= e($t['objectif']) ?></p>
    </div>
  </div>
</section>

<section class="section schema-projet" aria-labelledby="concret-titre">
  <div class="container">
    <h2 id="concret-titre"><?= e($t['concret_titre']) ?></h2>
    <figure>
      <img src="/assets/img/schema-caisse-locale.webp" alt="<?= e($t['schema_alt']) ?>" width="1920" height="1357" loading="lazy" decoding="async">
      <figcaption><details><summary><?= e($t['schema_lire']) ?></summary><p><?= e($t['schema_texte']) ?></p></details></figcaption>
    </figure>
    <ol class="etapes-chiffrees__liste">
      <?php foreach (['concret_1', 'concret_2', 'concret_3'] as $cle): ?><li><?= e($t[$cle]) ?></li><?php endforeach ?>
    </ol>
  </div>
</section>

<section class="section engage" aria-labelledby="engagement-titre">
  <div class="engage__inner container">
    <img class="engage__photo" src="/assets/img/groupe.webp" alt="" width="1280" height="400" loading="lazy" decoding="async">
    <div>
      <h2 id="engagement-titre"><?= e($t['engagement_titre']) ?></h2>
      <p><?= e($t['engagement_texte']) ?></p>
    </div>
  </div>
</section>

<section class="section appel" aria-labelledby="contact-titre">
  <div class="appel__inner container">
    <span class="appel__illustration" aria-hidden="true"><img src="/assets/img/illustration-contact.svg" alt="" width="913" height="680" loading="lazy" decoding="async"></span>
    <div>
      <h2 id="contact-titre"><?= e($t['contact_titre']) ?></h2>
      <p class="appel__actions">
        <a class="button" href="<?= e(lurl('/contact/')) ?>"><?= e(t('contact.nous_contacter')) ?></a>
        <a class="button button--ghost" href="<?= e(lurl('/lieux-conventionnes/')) ?>"><?= e(t('accueil.voir_lieux')) ?></a>
      </p>
    </div>
  </div>
</section>
