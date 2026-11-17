<?php
/** Politique de confidentialité (maquette docs/maquettes/confidentialite.md) : formulaire de contact, administration,
 * traceurs, droits. @var array<string, string> $t @var string $path */
partial('site/haut', ['chemin' => $path, 'etroit' => true, 'titre' => $t['titre'], 'chapo' => $t['intro']]);
$lignes = ['donnees', 'finalite', 'base', 'destinataires', 'conservation', 'abus', 'transfert'];
?>
<div class="section legal">
  <div class="legal__colonne container">
    <section aria-labelledby="contact-titre">
      <h2 id="contact-titre"><?= e($t['contact_titre']) ?></h2>
      <dl class="legal__liste">
        <?php foreach ($lignes as $cle): ?><div><dt><?= e($t['libelle_' . $cle]) ?></dt><dd><?= \App\Site\Texte::courriels($t['contact_' . $cle]) ?></dd></div><?php endforeach ?>
      </dl>
    </section>
    <section aria-labelledby="admin-titre"><h2 id="admin-titre"><?= e($t['admin_titre']) ?></h2><p><?= e($t['admin_texte']) ?></p></section>
    <section aria-labelledby="agenda-titre"><h2 id="agenda-titre"><?= e($t['agenda_titre']) ?></h2><p><?= e($t['agenda_texte']) ?></p></section>
    <section aria-labelledby="traceurs-titre"><h2 id="traceurs-titre"><?= e($t['traceurs_titre']) ?></h2><p><?= e($t['traceurs_texte']) ?></p></section>
    <section aria-labelledby="droits-titre">
      <h2 id="droits-titre"><?= e($t['droits_titre']) ?></h2>
      <p><?= \App\Site\Texte::courriels($t['droits_texte']) ?></p>
      <p><?= e($t['droits_cnil']) ?> <a href="https://www.cnil.fr/fr/plaintes" rel="noopener">cnil.fr</a></p>
    </section>
    <p class="legal__date"><?= e($t['mise_a_jour']) ?></p>
  </div>
</div>
