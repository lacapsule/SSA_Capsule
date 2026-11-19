<?php
/**
 * Contact (maquette docs/maquettes/contact.md), piste 2 : illustration du site actuel dans le haut de page,
 * coordonnées en encadré bleu marine (icône par ligne), formulaire protégé à côté.
 * @var array $t @var string $path @var array<string, mixed> $values @var array<string, string> $errors
 * @var string|null $alert @var bool $sent
 */
use App\Core\FormGuard;
use App\Site\Site;

$v = static fn (string $k): string => is_string($values[$k] ?? null) ? $values[$k] : '';
$decrit = static fn (string $k, string $aide = ''): string => trim(($aide !== '' ? $aide . ' ' : '') . (isset($errors[$k]) ? "c-$k-err" : ''));
$adresse = (array) Site::get('adresse', []);
$tel = (string) Site::get('telephone');
$email = (string) Site::get('email');
$position = !empty($adresse['position']) ? \App\Lieux\Positions::parCle((string) $adresse['position']) : null;
$ligneAdresse = trim(($adresse['rue'] ?? '') . ', ' . ($adresse['code_postal'] ?? '') . "\u{00A0}" . ($adresse['ville'] ?? ''), ' ,'); // code postal et commune jamais séparés
// Au téléphone, le formulaire arrive après les coordonnées et la carte : un lien y mène dès le haut de page (revue du 2026-11-17).
$aller = '<p class="contact__aller"><a class="lien-fleche" href="#formulaire-titre">' . e($t['formulaire_titre']) . '<svg viewBox="0 0 16 16" width="16" height="16" aria-hidden="true" focusable="false"><path d="M8 3v10M4 9l4 4 4-4" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></a></p>';
partial('site/haut', ['chemin' => $path, 'titre' => $t['titre'], 'chapo' => $t['chapo'], 'apres' => $aller, 'personnage' => '/assets/img/personnages/contact.svg', 'miroir' => true]); // personnage de la boîte à la place de l'illustration de banque d'images (2026-11-17)
$icone = static fn (string $d): string => '<svg class="coordonnees__icone" viewBox="0 0 24 24" width="24" height="24" aria-hidden="true" focusable="false"><path d="' . $d . '" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>';
?>
<section class="section" aria-labelledby="coordonnees-titre">
  <div class="container contact">
    <div class="contact__infos">
      <h2 id="coordonnees-titre"><?= e($t['coordonnees_titre']) ?></h2>
      <ul class="coordonnees">
        <li><?= $icone('M12 21s-7-6.2-7-11.5A7 7 0 0 1 19 9.5C19 14.8 12 21 12 21zM12 12a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5z') ?>
          <address><strong><?= e((string) ($adresse['lieu'] ?? '')) ?></strong><br><?= e($ligneAdresse) ?></address>
          <a class="lien-fleche" href="<?= e($position !== null ? 'https://www.openstreetmap.org/directions?to=' . $position['lat'] . ',' . $position['lon'] : 'https://www.openstreetmap.org/search?query=' . rawurlencode($ligneAdresse)) ?>" rel="noopener"><?= e(t('agenda.itineraire')) ?><svg viewBox="0 0 16 16" width="16" height="16" aria-hidden="true" focusable="false"><path d="M3 8h10M9 4l4 4-4 4" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></a></li>
        <li><?= $icone('M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2z') ?>
          <span class="coordonnees__libelle"><?= e(t('pied.telephone')) ?></span><a href="tel:+33<?= e(substr((string) preg_replace('/\D/', '', $tel), 1)) ?>"><?= e($tel) ?></a></li>
        <li><?= $icone('M3 6h18v12H3zM3 7l9 6 9-6') ?>
          <span class="coordonnees__libelle"><?= e(t('pied.email')) ?></span><a href="mailto:<?= e($email) ?>"><?= e($email) ?></a></li>
        <li><?= $icone('M10 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM3 21a7 7 0 0 1 14 0M19 8v6M16 11h6') ?>
          <span class="coordonnees__libelle"><?= e($t['candidatures']) ?></span><a href="mailto:<?= e((string) Site::get('email_candidatures')) ?>"><?= e((string) Site::get('email_candidatures')) ?></a></li>
      </ul>
    </div>

    <div class="contact__formulaire">
      <h2 id="formulaire-titre"><?= e($t['formulaire_titre']) ?></h2>
      <?php if ($sent): ?>
      <p class="alert alert--success" role="status"><?= e(t('contact.merci')) ?></p>
      <?php else: ?>
      <form class="form" method="post" action="<?= e(lurl('/contact/')) ?>" aria-labelledby="formulaire-titre" novalidate>
        <?= FormGuard::fields('contact') ?>
        <p class="field__hint"><?= e(t('contact.champs_requis')) ?></p>
        <?php if ($alert !== null): ?><p class="alert alert--error" role="alert"><?= e($alert) ?></p><?php endif ?>
        <?php if ($errors !== []): ?><p class="alert alert--error" role="alert"><?= e(t('contact.erreurs')) ?></p><?php endif ?>
        <div class="field">
          <label for="c-name"><?= e(t('contact.nom')) ?> <span aria-hidden="true">*</span></label>
          <?php if (isset($errors['name'])): ?><p class="field__error" id="c-name-err"><?= e($errors['name']) ?></p><?php endif ?>
          <input id="c-name" name="name" autocomplete="name" maxlength="120" value="<?= e($v('name')) ?>" required<?= isset($errors['name']) ? ' aria-invalid="true" aria-describedby="c-name-err"' : '' ?>>
        </div>
        <div class="field">
          <label for="c-email"><?= e(t('contact.email')) ?> <span aria-hidden="true">*</span></label>
          <?php if (isset($errors['email'])): ?><p class="field__error" id="c-email-err"><?= e($errors['email']) ?></p><?php endif ?>
          <input id="c-email" name="email" type="email" autocomplete="email" maxlength="190" value="<?= e($v('email')) ?>" required<?= isset($errors['email']) ? ' aria-invalid="true" aria-describedby="c-email-err"' : '' ?>>
        </div>
        <div class="field">
          <label for="c-objet"><?= e(t('contact.objet')) ?> (<?= e(t('contact.facultatif')) ?>)</label>
          <?php if (isset($errors['objet'])): ?><p class="field__error" id="c-objet-err"><?= e($errors['objet']) ?></p><?php endif ?>
          <input id="c-objet" name="objet" autocomplete="off" maxlength="150" value="<?= e($v('objet')) ?>"<?= isset($errors['objet']) ? ' aria-invalid="true" aria-describedby="c-objet-err"' : '' ?>>
        </div>
        <div class="field">
          <label for="c-message"><?= e(t('contact.message')) ?> <span aria-hidden="true">*</span></label>
          <p class="field__hint" id="c-message-aide"><?= e(t('contact.message_aide')) ?></p>
          <?php if (isset($errors['message'])): ?><p class="field__error" id="c-message-err"><?= e($errors['message']) ?></p><?php endif ?>
          <textarea id="c-message" name="message" rows="7" maxlength="5000" required aria-describedby="<?= e($decrit('message', 'c-message-aide')) ?>"<?= isset($errors['message']) ? ' aria-invalid="true"' : '' ?>><?= e($v('message')) ?></textarea>
        </div>
        <p class="field__hint"><?= e(t('contact.confidentialite_avant')) ?> <a href="<?= e(lurl('/confidentialite/')) ?>"><?= e(t('contact.confidentialite_lien')) ?></a>.</p>
        <button class="button" type="submit"><?= e(t('contact.envoyer')) ?></button>
      </form>
      <?php endif ?>
    </div>
  </div>
</section>
