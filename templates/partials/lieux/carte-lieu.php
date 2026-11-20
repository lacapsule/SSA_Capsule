<?php
/**
 * Carte d'un lieu (événement, contact ; décision du 2026-11-17) : si la position est connue (App\Lieux\Positions), le
 * quartier dessiné d'après l'IGN (rues, cours d'eau) avec un repère au point exact ; sinon la commune lue dans l'adresse ;
 * sinon rien. Zoom, déplacement et clavier par carte-zoom.js ; la page appelante charge ce script et met l'itinéraire.
 * @var string $adresse adresse saisie @var string $libelle nom du lieu près du repère @var ?string $cle position (contact)
 * @var string $titre nom accessible de la carte (« {lieu} sur la carte »)
 * @var ?array{lat: float, lon: float, precision: string, cle: string, ign: string} $position facultatif @var ?array<string, mixed> $quartier facultatif
 */
use App\Lieux\Positions;

// position et quartier donnés par l'appelant (événement placé par l'IGN à l'enregistrement, décision du 2026-11-20),
// sinon la liste versionnée des lieux d'événements.
$position = isset($position) && is_array($position) ? $position : (!empty($cle) ? Positions::parCle((string) $cle) : Positions::pour((string) $adresse));
$quartier = isset($quartier) && is_array($quartier) ? $quartier : ($position !== null && ($position['cle'] ?? '') !== '' ? Positions::quartier($position['cle']) : null);
$icone = static fn (string $d): string => '<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" focusable="false"><path d="' . $d . '" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
$controles = static function () use ($icone): void { ?>
  <div class="carte-lieu__controles" hidden data-controles>
    <button type="button" data-zoom="plus" hidden><?= $icone('M12 5v14M5 12h14') ?><span class="visually-hidden"><?= e(t('carte.zoom_plus')) ?></span></button>
    <button type="button" data-zoom="moins" hidden><?= $icone('M5 12h14') ?><span class="visually-hidden"><?= e(t('carte.zoom_moins')) ?></span></button>
    <button type="button" data-zoom="depart" hidden><?= $icone('M3 11l9-7 9 7M5 10v10h5v-6h4v6h5V10') ?><span class="visually-hidden"><?= e(t('carte.zoom_depart')) ?></span></button>
  </div>
<?php };
if ($quartier !== null):
    $r = 1600;
    $nom = trim((string) ($libelle ?? ''));
    // Commune lue dans le résultat de l'IGN (« … 29600 Morlaix », « …, Plouégat-Guérand ») : repère hors du quartier.
    $ign = trim((string) preg_replace('/\s*\(.*$/u', '', $position['ign'])); // sans la source notée entre parenthèses
    $commune = preg_match('/\d{5}\s+(.+)$/u', $ign, $m) === 1 ? $m[1] : (preg_match('/,\s*([^,]+)$/u', $ign, $m) === 1 ? $m[1] : '');
    $titre = str_replace('{lieu}', $nom !== '' ? $nom : (string) $adresse, (string) $titre);
?>
<div class="carte-lieu" tabindex="0" aria-label="<?= e($titre . ' : ' . t('carte.clavier')) ?>" data-carte-zoom>
  <svg class="carte-quartier" viewBox="-1200 -840 2400 1680" data-limites="<?= -$r ?> <?= -$r ?> <?= 2 * $r ?> <?= 2 * $r ?>" data-zoom-max="6" role="img" aria-labelledby="carte-lieu-titre" focusable="false">
    <title id="carte-lieu-titre"><?= e($titre) ?></title>
    <rect class="carte-quartier__fond" x="<?= -2 * $r ?>" y="<?= -2 * $r ?>" width="<?= 4 * $r ?>" height="<?= 4 * $r ?>"/>
    <path class="carte-quartier__eau" d="<?= e((string) $quartier['eau']) ?>"/>
    <path class="carte-quartier__route carte-quartier__route--petite" d="<?= e((string) $quartier['routes']['petites']) ?>"/>
    <path class="carte-quartier__route carte-quartier__route--moyenne" d="<?= e((string) $quartier['routes']['moyennes']) ?>"/>
    <path class="carte-quartier__route carte-quartier__route--grande" d="<?= e((string) $quartier['routes']['grandes']) ?>"/>
    <g class="carte-quartier__repere">
      <path d="M0 0C-5-9-13-15-13-24a13 13 0 1 1 26 0C13-15 5-9 0 0z"/>
      <circle cx="0" cy="-24" r="5"/>
      <?php if ($nom !== ''): ?><text x="18" y="-20"><?= e($nom) ?></text><?php endif ?>
    </g>
  </svg>
  <?php if ($commune !== ''): ?><span class="carte-lieu__commune" aria-hidden="true"><?= e($commune) ?></span><?php endif ?>
  <?php $controles() ?>
  <small class="carte-lieu__credit"><?= e(t('carte.credit_ign')) ?><?= $position['precision'] !== 'adresse' ? ' · ' . e(t('carte.precision_' . $position['precision'])) : '' ?></small>
</div>
<?php
else:
    ob_start();
    partial('lieux/carte-commune', ['adresse' => (string) $adresse, 'titre' => t('agenda.commune_sur_carte')]);
    $commune = (string) ob_get_clean();
    if ($commune !== ''):
?>
<div class="carte-lieu carte-lieu--commune" tabindex="0" aria-label="<?= e(t('carte.clavier')) ?>" data-carte-zoom>
  <?= $commune // carte de la commune, déjà échappée ?>
  <?php $controles() ?>
  <small class="carte-lieu__credit"><?= e(t('carte.credit_commune')) ?></small>
</div>
<?php
    endif;
endif;
