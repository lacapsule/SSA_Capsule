<?php
/**
 * Lieux conventionnés (maquette docs/maquettes/lieux-conventionnes.md ; décisions du 2026-11-17) : l'outil « Trouver
 * un lieu » (partial lieux/explorer : outils, carte dessinée avec sa légende des quatre calques, résultats, bouton
 * lien vers la carte en grand), texte de l'ancien article 16 dans le panneau de gauche tant que rien n'est choisi.
 * @var array $t @var string $path @var list<array> $lieux @var list<array> $groupes @var array<string, int> $calques
 */
partial('site/haut', ['chemin' => $path, 'titre' => $t['titre'], 'chapo' => $t['chapo']]);
?>
<?php if ($groupes !== []): ?>
<section class="section lieux-liste" aria-labelledby="liste-titre">
  <div class="container">
    <div class="lieux-liste__tete">
      <h2 id="liste-titre"><?= e($t['liste_titre']) ?></h2>
      <p><?= e($t['liste_texte']) ?></p>
    </div>
    <?php partial('lieux/explorer', ['t' => $t, 'groupes' => $groupes, 'calques' => $calques]) ?>
  </div>
</section>
<?php endif ?>

<?php partial('site/appel', ['titre' => $t['proposer_titre'], 'texte' => $t['proposer_texte'], 'actions' => [['href' => '/contact/', 'libelle' => t('contact.nous_contacter')]], 'image' => '/assets/img/personnages/lieux.svg', 'compact' => true]) ?>
