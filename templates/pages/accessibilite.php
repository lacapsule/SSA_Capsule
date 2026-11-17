<?php
/** Accessibilité (maquette docs/maquettes/accessibilite.md ; rule web-a11y) : niveau d'accessibilité en tête (référentiel,
 * puis niveau, date et méthode d'un audit réel, ou une phrase qui dit que l'audit reste à faire) (config/site.php, « accessibilite ») ; jamais de mention « partiellement conforme ».
 * @var array<string, string> $t @var string $path */
use App\Site\Dates;
use App\Site\Site;

$a11y = (array) Site::get('accessibilite', []);
$audite = !empty($a11y['niveau']) && !empty($a11y['date_audit']) && !empty($a11y['methode']);
partial('site/haut', ['chemin' => $path, 'etroit' => true, 'titre' => $t['titre'], 'chapo' => $t['intro']]);
?>
<div class="section legal">
  <div class="legal__colonne container">
    <section aria-labelledby="niveau-titre">
      <h2 id="niveau-titre"><?= e($t['niveau_titre']) ?></h2>
      <dl class="legal__liste">
        <div><dt><?= e($t['libelle_referentiel']) ?></dt><dd><?= e((string) ($a11y['referentiel'] ?? '')) ?></dd></div>
        <?php if ($audite): ?>
        <div><dt><?= e($t['libelle_niveau']) ?></dt><dd class="legal__niveau"><?= e((string) $a11y['niveau']) ?></dd></div>
        <div><dt><?= e($t['libelle_audit']) ?></dt><dd><?= e(Dates::longue((string) $a11y['date_audit'])) ?> : <?= e((string) $a11y['methode']) ?></dd></div>
        <?php else: ?>
        <div><dt><?= e($t['libelle_audit']) ?></dt><dd><?= e($t['audit_attente']) ?></dd></div>
        <?php endif ?>
      </dl>
    </section>
    <section aria-labelledby="limites-titre"><h2 id="limites-titre"><?= e($t['limites_titre']) ?></h2><ul class="puces"><li><?= e($t['limite_breton']) ?></li></ul></section>
    <section aria-labelledby="signaler-titre">
      <h2 id="signaler-titre"><?= e($t['contact_titre']) ?></h2>
      <p><?= \App\Site\Texte::courriels($t['contact_texte']) ?></p>
      <p><a class="button button--ghost" href="<?= e(lurl('/contact/')) ?>"><?= e(t('contact.nous_contacter')) ?></a></p>
    </section>
    <section aria-labelledby="recours-titre"><h2 id="recours-titre"><?= e($t['recours_titre']) ?></h2><p><?= e($t['recours_texte']) ?> <a href="https://www.defenseurdesdroits.fr/" rel="noopener">defenseurdesdroits.fr</a></p></section>
    <p class="legal__date"><?= e($t['mise_a_jour']) ?></p>
  </div>
</div>
