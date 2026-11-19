<?php
/**
 * Page introuvable (rule web-seo : gabarit du site, noindex, liens vers l'accueil, les pages principales et le plan du
 * site) : page isolée, tout centré (rule web-design, « Centrage ») ; « 404 » en grand avec le personnage dans le zéro
 * (point focal), l'adresse demandée rappelée, un seul bouton principal.
 * @var string $demande chemin demandé (affiché échappé)
 */
$liens = ['/agenda/' => 'menu.agenda', '/lieux-conventionnes/' => 'menu.lieux', '/actualites/' => 'menu.actualites', '/plan-du-site/' => 'pied.plan_du_site'];
$demande = isset($demande) && is_string($demande) ? $demande : '';
?>
<section class="section introuvable" aria-labelledby="introuvable-titre">
  <div class="container introuvable__inner">
    <p class="introuvable__code" aria-hidden="true"><span>4</span><span class="portail portail--introuvable portail--buste"><span class="portail__calque"><img src="/assets/img/personnages/introuvable.svg" alt="" width="309" height="321" decoding="async"></span></span><span>4</span></p>
    <p class="sur-titre">Erreur 404</p>
    <h1 id="introuvable-titre">Cette page est introuvable</h1>
    <p class="introuvable__texte"><?php if ($demande !== ''): ?>L'adresse <code><?= e($demande) ?></code> n'existe pas sur ce site, ou elle a changé.<?php else: ?>L'adresse demandée n'existe pas sur ce site, ou elle a changé.<?php endif ?> Vérifiez-la, ou repartez d'une des pages ci-dessous.</p>
    <p class="introuvable__actions">
      <a class="button" href="<?= e(lurl('/')) ?>"><?= e(t('menu.accueil')) ?></a>
      <?php foreach ($liens as $href => $cle): ?><a class="button button--ghost" href="<?= e(lurl($href)) ?>"><?= e(t($cle)) ?></a><?php endforeach ?>
    </p>
  </div>
</section>
