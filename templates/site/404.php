<?php
/**
 * Page introuvable (rule web-seo : gabarit du site, noindex, liens vers l'accueil, les pages principales et le plan du
 * site) : page isolée, donc en-tête et actions centrés (rule web-design, « Centrage ») ; un seul bouton principal.
 */
$liens = ['/agenda/' => 'menu.agenda', '/lieux-conventionnes/' => 'menu.lieux', '/actualites/' => 'menu.actualites', '/plan-du-site/' => 'pied.plan_du_site'];
?>
<div class="page-head page-head--centre">
  <div class="page-head__inner container">
    <h1>Page introuvable</h1>
    <p class="page-head__intro">L'adresse demandée n'existe pas ou a changé.</p>
  </div>
</div>
<section class="section introuvable" aria-labelledby="introuvable-titre">
  <div class="container">
    <h2 id="introuvable-titre" class="introuvable__titre">Pour repartir</h2>
    <p class="introuvable__actions">
      <a class="button" href="<?= e(lurl('/')) ?>"><?= e(t('menu.accueil')) ?></a>
      <?php foreach ($liens as $href => $cle): ?><a class="button button--ghost" href="<?= e(lurl($href)) ?>"><?= e(t($cle)) ?></a><?php endforeach ?>
    </p>
  </div>
</section>
