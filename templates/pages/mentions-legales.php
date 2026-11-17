<?php
/** Mentions légales (maquette docs/maquettes/mentions-legales.md) : éditeur, publication, contact, hébergeur, crédits.
 * Identité, coordonnées et crédit : config/site.php (source unique). @var array<string, string> $t @var string $path */
use App\Site\Site;

$ed = (array) Site::get('editeur', []);
$heb = (array) Site::get('hebergeur', []);
$adr = (array) Site::get('adresse', []);
$tel = (string) Site::get('telephone');
$mail = (string) Site::get('email');
$credit = (array) Site::get('credit', []);
$telLien = static fn (string $n): string => 'tel:+33' . substr((string) preg_replace('/\D/', '', $n), 1);
partial('site/haut', ['chemin' => $path, 'etroit' => true, 'titre' => $t['titre'], 'chapo' => '']);
?>
<div class="section legal">
  <div class="legal__colonne container">
    <section aria-labelledby="projet-titre"><h2 id="projet-titre"><?= e($t['projet_titre']) ?></h2><p><?= e($t['projet_texte']) ?></p><p><?= \App\Site\Texte::courriels($t['referente']) ?></p></section>
    <section aria-labelledby="editeur-titre">
      <h2 id="editeur-titre"><?= e($t['editeur_titre']) ?></h2>
      <p><?= e($t['editeur_texte']) ?></p>
      <address><strong><?= e((string) ($ed['nom'] ?? '')) ?></strong><br><?= e((string) ($ed['adresse'] ?? '')) ?><?php if (!empty($ed['siret'])): ?><br>SIRET : <?= e((string) $ed['siret']) ?><?php endif ?></address>
    </section>
    <section aria-labelledby="publication-titre"><h2 id="publication-titre"><?= e($t['publication_titre']) ?></h2><p><?= e($t['publication_texte']) ?></p></section>
    <section aria-labelledby="contact-titre">
      <h2 id="contact-titre"><?= e($t['contact_titre']) ?></h2>
      <address><?= e((string) ($adr['lieu'] ?? '')) ?>, <?= e((string) ($adr['rue'] ?? '')) ?>, <?= e(($adr['code_postal'] ?? '') . ' ' . ($adr['ville'] ?? '')) ?><br>
      <?= e(t('pied.telephone')) ?> : <a href="<?= e($telLien($tel)) ?>"><?= e($tel) ?></a><br>
      <?= e(t('pied.email')) ?> : <a href="mailto:<?= e($mail) ?>"><?= e($mail) ?></a></address>
    </section>
    <section aria-labelledby="hebergement-titre">
      <h2 id="hebergement-titre"><?= e($t['hebergement_titre']) ?></h2>
      <address><strong><?= e((string) ($heb['nom'] ?? '')) ?></strong><br><?= e((string) ($heb['adresse'] ?? '')) ?><br>
      <?= e(t('pied.telephone')) ?> : <a href="<?= e($telLien((string) ($heb['telephone'] ?? ''))) ?>"><?= e((string) ($heb['telephone'] ?? '')) ?></a><br>
      <?= e(t('pied.email')) ?> : <a href="mailto:<?= e((string) ($heb['email'] ?? '')) ?>"><?= e((string) ($heb['email'] ?? '')) ?></a></address>
    </section>
    <section aria-labelledby="credits-titre">
      <h2 id="credits-titre"><?= e($t['credits_titre']) ?></h2>
      <?php if ($credit !== []): ?><p><?= e($credit['avant']) ?><a href="<?= e($credit['lien']['url']) ?>" rel="noopener"><?= e($credit['lien']['texte']) ?></a><?= e($credit['apres']) ?><?php if (!empty($credit['complement'])): ?><?= e($credit['complement'][0]) ?><a href="<?= e($credit['complement'][1]['url']) ?>" rel="noopener"><?= e($credit['complement'][1]['texte']) ?></a><?php endif ?>.</p><?php endif ?>
      <p><?= e($t['credits_photos']) ?></p>
      <p><?= e($t['credits_droits']) ?></p>
    </section>
    <p class="legal__date"><?= e($t['mise_a_jour']) ?></p>
  </div>
</div>
