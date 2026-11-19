<?php
/**
 * Squelettes de chargement (composants/etat-vide-chargement) : lignes (liste, tableau) ou cartes.
 * Usage : $chargement = [...]; require __DIR__ . '/chargement.php';
 * Le conteneur qui se remplit porte aria-busy="true" pendant l'attente (le retirer à l'arrivée des données).
 * Suppose la fonction d'échappement du socle : function e(string $v): string.
 */

/** @var array{libelle: string, forme?: string, nombre?: int} $chargement */
$chargement ??= [
    'libelle' => "Chargement de l'activité récente…", // lu par les lecteurs d'écran
    'forme' => 'lignes',                               // lignes | cartes
    'nombre' => 4,
];

$nombre = max(1, min(12, (int) ($chargement['nombre'] ?? 4)));
$cartes = ($chargement['forme'] ?? 'lignes') === 'cartes';
?>
<div role="status"><span class="adm-masque"><?= e($chargement['libelle']) ?></span></div>
<?php if ($cartes): ?>
<div class="adm-squelette-cartes" aria-hidden="true">
  <?php for ($i = 0; $i < $nombre; $i++): ?>
  <div class="adm-squelette-carte"><span class="adm-squelette adm-squelette--court"></span><span class="adm-squelette adm-squelette--chiffre"></span><span class="adm-squelette adm-squelette--texte"></span><span class="adm-squelette adm-squelette--pastille"></span></div>
  <?php endfor ?>
</div>
<?php else: ?>
<ul class="adm-squelette-lignes" aria-hidden="true">
  <?php for ($i = 0; $i < $nombre; $i++): ?>
  <li><span class="adm-squelette adm-squelette--avatar"></span><span class="adm-squelette-lignes__textes"><span class="adm-squelette adm-squelette--texte"></span><span class="adm-squelette adm-squelette--court"></span></span><span class="adm-squelette adm-squelette--valeur"></span></li>
  <?php endfor ?>
</ul>
<?php endif ?>
