<?php
/**
 * Lieux conventionnés (maquette docs/maquettes/lieux-conventionnes.md). La liste se masque tant qu'elle est vide
 * (à fournir : Géraldine Gabillet) ; le texte et la carte en ligne de l'ancien article 16 restent.
 * @var array $t @var string $path @var list<array> $lieux
 */
use App\Site\Texte;

partial('site/haut', ['chemin' => $path, 'titre' => $t['titre'], 'chapo' => $t['chapo']]);
$adresse = static fn (array $l): string => trim($l['adresse'] . ', ' . $l['code_postal'] . ' ' . $l['ville'], ' ,');
?>
<section class="section">
  <div class="container prose">
    <h2><?= e($t['choix_titre']) ?></h2>
    <p><?= e($t['choix_texte']) ?></p>
    <p><?= e($t['publie']) ?></p>
  </div>
</section>

<section class="section section--fond" aria-labelledby="carte-titre">
  <div class="container duo duo--carte">
    <a class="carte-apercu" href="<?= e($t['carte_url']) ?>" rel="noopener"><img src="/assets/img/carte-points-de-vente.webp" alt="<?= e($t['carte_image_alt']) ?>" width="1603" height="871" loading="lazy" decoding="async"></a>
    <div class="prose">
    <h2 id="carte-titre"><?= e($t['carte_titre']) ?></h2>
    <p><?= e($t['carte_texte']) ?></p>
    <ul class="puces">
      <?php foreach ([1, 2, 3, 4] as $n): ?><li><?= e($t['calque_' . $n]) ?></li><?php endforeach ?>
    </ul>
    <p><a class="button" href="<?= e($t['carte_url']) ?>" rel="noopener"><?= e($t['carte_lien']) ?></a></p>
    </div>
  </div>
</section>

<?php if ($lieux !== []): ?>
<section class="section" aria-labelledby="liste-titre">
  <div class="container">
    <div class="section__head"><h2 id="liste-titre"><?= e($t['liste_titre']) ?></h2></div>
    <ul class="lieux">
      <?php foreach ($lieux as $l): ?>
      <li class="lieu">
        <h3 class="lieu__nom"<?= langue_attr($l, 'nom') ?>><?= e((string) $l['nom']) ?></h3>
        <p class="lieu__adresse"><?= e($adresse($l)) ?></p>
        <?php if (!empty($l['horaires'])): ?><div class="lieu__horaires"<?= langue_attr($l, 'horaires') ?>><?= Texte::paragraphes((string) $l['horaires']) ?></div><?php endif ?>
        <p><a class="lien-fleche" href="https://www.openstreetmap.org/search?query=<?= e(rawurlencode($adresse($l))) ?>" rel="noopener"><?= e(t('agenda.itineraire')) ?></a></p>
      </li>
      <?php endforeach ?>
    </ul>
  </div>
</section>
<?php endif ?>

<section class="section cta" aria-labelledby="proposer-titre">
  <div class="container">
    <h2 id="proposer-titre"><?= e($t['proposer_titre']) ?></h2>
    <p><a class="button" href="<?= e(lurl('/contact/')) ?>"><?= e(t('contact.nous_contacter')) ?></a></p>
  </div>
</section>
