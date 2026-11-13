<?php
/**
 * Contact (maquette docs/maquettes/contact.md) : coordonnées et illustration du site actuel, formulaire protégé.
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
$ligneAdresse = trim(($adresse['rue'] ?? '') . ', ' . ($adresse['code_postal'] ?? '') . ' ' . ($adresse['ville'] ?? ''), ' ,');
partial('site/haut', ['chemin' => $path, 'titre' => $t['titre'], 'chapo' => $t['chapo']]);
?>
<section class="section">
  <div class="container contact">
    <div class="contact__infos">
      <h2><?= e($t['coordonnees_titre']) ?></h2>
      <address>
        <strong><?= e((string) ($adresse['lieu'] ?? '')) ?></strong><br>
        <?= e($ligneAdresse) ?><br>
        <?= e(t('pied.telephone')) ?> : <a href="tel:+33<?= e(substr((string) preg_replace('/\D/', '', $tel), 1)) ?>"><?= e($tel) ?></a><br>
        <?= e(t('pied.email')) ?> : <a href="mailto:<?= e($email) ?>"><?= e($email) ?></a><br>
        <?= e($t['candidatures']) ?> : <a href="mailto:<?= e((string) Site::get('email_candidatures')) ?>"><?= e((string) Site::get('email_candidatures')) ?></a>
      </address>
      <p><a class="button button--ghost" href="https://www.openstreetmap.org/search?query=<?= e(rawurlencode($ligneAdresse)) ?>" rel="noopener"><?= e(t('agenda.itineraire')) ?></a></p>
      <img class="contact__illustration" src="/assets/img/illustration-contact.svg" alt="" width="913" height="680" loading="lazy" decoding="async">
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
