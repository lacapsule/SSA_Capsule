<?php
/**
 * Fiches voisines (brique sections/navigation-fiches de la boîte, adaptée dans ssa.css) et retour à la rubrique.
 * @var ?array{titre: string, chemin: string, vignette?: ?string} $precedent @var ?array{titre: string, chemin: string, vignette?: ?string} $suivant
 * @var array{chemin: string, libelle: string} $rubrique @var string $nature article ou evenement (intitulés)
 */
$carte = static fn (?array $f): ?array => $f === null ? null : ['url' => lurl($f['chemin']), 'titre' => $f['titre'], 'vignette' => $f['vignette'] ?? null];
?>
<div class="section voisins">
<?= navigation_fiches($carte($precedent), $carte($suivant), [
    'nav' => t('site.voisins_' . $nature),
    'precedent' => t('site.precedent_' . $nature),
    'suivant' => t('site.suivant_' . $nature),
    'retour' => $rubrique['libelle'],
    'urlRetour' => lurl($rubrique['chemin']),
]) ?>
</div>
