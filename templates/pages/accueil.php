<?php
/** Accueil (maquette docs/maquettes/accueil.md). @var array<string, string> $t textes de la page dans la langue affichée */
use App\Site\Site;
?>
<div class="page-head page-head--accueil">
  <div class="page-head__inner container">
    <h1><?= e($t['titre']) ?></h1>
    <p class="page-head__intro"><?= e($t['devise']) ?></p>
    <p class="page-head__actions">
      <a class="button" href="<?= e(lurl('/agenda/')) ?>"><?= e(t('accueil.voir_agenda')) ?></a>
      <a class="button button--ghost" href="<?= e(lurl('/lieux-conventionnes/')) ?>"><?= e(t('accueil.trouver_lieu')) ?></a>
    </p>
  </div>
</div>

<section class="section section--etapes" aria-labelledby="etapes-titre">
  <div class="container">
    <div class="section__head"><h2 id="etapes-titre"><?= e($t['etapes_titre']) ?></h2></div>
    <ol class="frise">
      <?php foreach (['etape_1', 'etape_2', 'etape_3'] as $cle): ?>
      <li class="frise__item"><span class="frise__dot" aria-hidden="true"></span><p><?= e($t[$cle]) ?></p></li>
      <?php endforeach ?>
    </ol>
    <p class="more"><a href="<?= e(lurl('/projet/')) ?>"><?= e(t('accueil.comprendre_projet')) ?></a></p>
  </div>
</section>

<section class="section section--fond" aria-labelledby="collectif-titre">
  <div class="container prose">
    <h2 id="collectif-titre"><?= e($t['collectif_titre']) ?></h2>
    <p><?= e($t['collectif_texte']) ?></p>
    <p class="more"><a href="<?= e(lurl('/collectif/')) ?>"><?= e(t('accueil.decouvrir_collectif')) ?></a></p>
  </div>
</section>

<section class="section cta" aria-labelledby="contact-titre">
  <div class="container">
    <h2 id="contact-titre"><?= e($t['contact_titre']) ?></h2>
    <p><a class="button" href="<?= e(lurl('/contact/')) ?>"><?= e(t('contact.nous_contacter')) ?></a></p>
  </div>
</section>
