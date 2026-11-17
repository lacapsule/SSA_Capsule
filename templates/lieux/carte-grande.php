<?php
/**
 * Carte des lieux en grand (décisions du 2026-11-17) : ouverte dans un nouvel onglet depuis la page des lieux, comme la
 * carte en ligne du collectif mais sous l'en-tête du site : carte sur toute la hauteur de l'écran (zoom et déplacement à
 * la souris, au doigt et au clavier), outils et légende posés dessus, fiches dans une barre latérale à droite qui
 * s'ouvre au choix d'une commune. Non indexée (la page des lieux reste la page de référence).
 * @var array $t @var string $path @var list<array> $groupes @var array<string, int> $calques
 */
?>
<div class="carte-page">
  <h1 class="visually-hidden"><?= e($t['carte_page_titre']) ?></h1>
  <?php partial('lieux/explorer', ['t' => $t, 'groupes' => $groupes, 'calques' => $calques, 'pageCarte' => true]) ?>
</div>
