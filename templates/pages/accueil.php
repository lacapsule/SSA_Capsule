<?php
/**
 * Accueil (maquette docs/maquettes/accueil.md) : le vivant d'abord, événements juste sous le haut de page.
 * @var array<string, string> $t textes de la page @var list<array> $evenements @var list<array> $articles
 * @var array<string, string> $collectif textes de la page Qui sommes-nous (structures, objectif), source unique
 */
?>
<div class="heros">
  <img class="heros__photo" src="/assets/img/bandeau.webp" alt="" width="1920" height="1281" fetchpriority="high" decoding="async">
  <div class="heros__inner container">
    <h1><?= e($t['titre']) ?></h1>
    <p class="heros__devise"><?= e($t['devise']) ?></p>
    <p class="heros__actions">
      <a class="button" href="<?= e(lurl('/agenda/')) ?>"><?= e(t('accueil.voir_agenda')) ?></a>
      <a class="button button--ghost" href="<?= e(lurl('/lieux-conventionnes/')) ?>"><?= e(t('accueil.trouver_lieu')) ?></a>
    </p>
  </div>
</div>

<?php if ($evenements !== []): ?>
<section class="section section--evenements" aria-labelledby="evenements-titre">
  <div class="container">
    <div class="section__head section__head--lien"><h2 id="evenements-titre"><?= e(t('accueil.prochains_evenements')) ?></h2><a class="lien-fleche" href="<?= e(lurl('/agenda/')) ?>"><?= e(t('accueil.tout_agenda')) ?><svg viewBox="0 0 16 16" width="16" height="16" aria-hidden="true" focusable="false"><path d="M3 8h10M9 4l4 4-4 4" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></a></div>
    <ul class="evenements"><?php foreach ($evenements as $e) partial('agenda/carte', ['e' => $e]) ?></ul>
  </div>
</section>
<?php endif ?>

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

<?php if ($articles !== []): ?>
<section class="section section--fond" aria-labelledby="actualites-titre">
  <div class="container">
    <div class="section__head section__head--lien"><h2 id="actualites-titre"><?= e(t('accueil.dernieres_actualites')) ?></h2><a class="lien-fleche" href="<?= e(lurl('/actualites/')) ?>"><?= e(t('accueil.toutes_actualites')) ?><svg viewBox="0 0 16 16" width="16" height="16" aria-hidden="true" focusable="false"><path d="M3 8h10M9 4l4 4-4 4" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></a></div>
    <ul class="cartes-articles"><?php foreach ($articles as $a) partial('actualites/carte', ['a' => $a]) ?></ul>
  </div>
</section>
<?php endif ?>

<section class="section" aria-labelledby="collectif-titre">
  <div class="container duo">
    <span class="portail" aria-hidden="true"><span class="portail__calque"><img src="/assets/img/illustration-collectif.svg" alt="" width="240" height="324" loading="lazy" decoding="async"></span></span>
    <div class="prose">
      <h2 id="collectif-titre"><?= e($t['collectif_titre']) ?></h2>
      <p class="sous-titre"><?= e($t['collectif_soustitre']) ?></p>
      <p><?= e($t['collectif_texte']) ?></p>
      <h3><?= e($collectif['structures_titre']) ?></h3>
      <ul class="puces">
        <?php foreach ([1, 2, 3, 4] as $n): ?>
        <li><strong><?= e($collectif['structure_' . $n . '_nom']) ?></strong> : <?= e($collectif['structure_' . $n . '_texte']) ?></li>
        <?php endforeach ?>
      </ul>
      <p><strong><?= e($collectif['objectif_titre']) ?></strong> : <?= e($collectif['objectif_texte']) ?></p>
      <p><a class="button" href="<?= e(lurl('/collectif/')) ?>"><?= e(t('accueil.decouvrir_collectif')) ?></a></p>
    </div>
  </div>
</section>

<?php partial('site/logos', ['titre_id' => 'partenaires-titre']) ?>

<section class="section cta" aria-labelledby="contact-titre">
  <div class="container">
    <h2 id="contact-titre"><?= e($t['contact_titre']) ?></h2>
    <p><a class="button" href="<?= e(lurl('/contact/')) ?>"><?= e(t('contact.nous_contacter')) ?></a></p>
  </div>
</section>
