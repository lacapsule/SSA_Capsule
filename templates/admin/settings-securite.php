<?php
/**
 * Réglages de sécurité du compte connecté : double authentification, codes de secours, mot de passe, activité récente.
 * @var array<string, mixed> $admin @var int $recoveryLeft @var list<array<string, mixed>> $log
 * @var array<string, string> $pwErrors @var list<string>|null $newCodes
 */
use App\Admin\AdminAuth;
use App\Admin\AdminController as A;
use App\Core\Csrf;

$labels = [
    'connexion' => 'Connexion',
    'deconnexion' => 'Déconnexion',
    '2fa.activee' => 'Double authentification activée',
    'code-secours.utilise' => 'Code de secours utilisé',
    'codes-secours.regeneres' => 'Codes de secours renouvelés',
    'mot-de-passe.change' => 'Mot de passe changé',
    'mot-de-passe.lien' => 'Lien « mot de passe oublié » envoyé',
    'mot-de-passe.reinitialise.lien' => 'Mot de passe changé par le lien reçu',
    'mot-de-passe.reinitialise' => 'Mot de passe réinitialisé sur le serveur',
    'mot-de-passe.reinitialise.2fa' => 'Mot de passe et double authentification réinitialisés sur le serveur',
];
$action = e(A::url('securite/'));
?>
<header class="adm-entete-page">
  <div class="adm-entete-page__textes">
    <h1 class="adm-entete-page__titre">Sécurité du compte</h1>
    <p class="adm-entete-page__sous-titre">Double authentification, codes de secours, mot de passe et activité récente de votre compte.</p>
  </div>
</header>
<div class="adm-pile securite">
  <section class="adm-carte adm-carte--simple securite-bloc" aria-labelledby="s-2fa">
    <h2 class="adm-carte__titre" id="s-2fa">Double authentification</h2>
    <?php if ($admin['totp_enabled_at'] !== null): ?>
      <div class="securite-ligne">
        <p><span class="pastille pastille--succes">Active</span> Application d'authentification reliée le <?= e(date('d/m/Y', (int) strtotime((string) $admin['totp_enabled_at']))) ?>.</p>
        <form method="post" action="<?= $action ?>"><?= Csrf::field() ?><input type="hidden" name="action" value="reenroll"><button class="btn btn--neutre" type="submit">Changer de téléphone</button></form>
      </div>
      <div class="securite-ligne">
        <p><span class="pastille <?= $recoveryLeft > 3 ? 'pastille--succes' : 'pastille--attention' ?>"><?= $recoveryLeft ?> sur <?= AdminAuth::RECOVERY_COUNT ?></span> codes de secours encore utilisables.</p>
        <form method="post" action="<?= $action ?>"><?= Csrf::field() ?><input type="hidden" name="action" value="codes"><button class="btn btn--neutre" type="submit">Créer de nouveaux codes</button></form>
      </div>
      <?php if ($newCodes): ?>
        <p class="adm-alerte adm-alerte--attention" role="status">Notez ces codes maintenant : ils ne seront plus affichés.</p>
        <ul class="auth-codes" aria-label="Nouveaux codes de secours"><?php foreach ($newCodes as $c): ?><li><?= e($c) ?></li><?php endforeach ?></ul>
      <?php endif ?>
    <?php else: ?>
      <div class="securite-ligne">
        <p><span class="pastille pastille--erreur">À configurer</span> Aucune application d'authentification n'est reliée à ce compte.</p>
        <form method="post" action="<?= $action ?>"><?= Csrf::field() ?><input type="hidden" name="action" value="reenroll"><button class="btn btn--neutre" type="submit">Configurer</button></form>
      </div>
    <?php endif ?>
  </section>

  <section class="adm-carte adm-carte--simple securite-bloc" aria-labelledby="mot-de-passe">
    <h2 class="adm-carte__titre" id="mot-de-passe">Changer mon mot de passe</h2>
    <form class="adm-pile securite-form" method="post" action="<?= $action ?>" novalidate>
      <?= Csrf::field() ?><input type="hidden" name="action" value="password">
      <?php if ($pwErrors): ?><p class="adm-alerte adm-alerte--erreur" role="alert">Mot de passe inchangé : corrigez le champ signalé.</p><?php endif ?>
      <?php partial('password', ['id' => 'p-cur', 'name' => 'current', 'label' => 'Mot de passe actuel', 'autocomplete' => 'current-password', 'error' => $pwErrors['current'] ?? null]) ?>
      <?php partial('password', ['id' => 'p-new', 'name' => 'new', 'label' => 'Nouveau mot de passe', 'autocomplete' => 'new-password', 'hint' => 'Au moins ' . AdminAuth::MIN_PASSWORD . ' caractères. Vos autres sessions ouvertes seront fermées.', 'error' => $pwErrors['new'] ?? null]) ?>
      <?php partial('password', ['id' => 'p-confirm', 'name' => 'confirm', 'label' => 'Confirmer le nouveau mot de passe', 'autocomplete' => 'new-password', 'error' => $pwErrors['confirm'] ?? null]) ?>
      <button class="btn" type="submit">Changer mon mot de passe</button>
    </form>
    <form class="securite-ligne" method="post" action="<?= $action ?>">
      <?= Csrf::field() ?><input type="hidden" name="action" value="password-link">
      <p class="adm-discret">Mot de passe actuel oublié ? Recevez un lien à <b><?= e((string) $admin['email']) ?></b> pour en choisir un nouveau (valable <?= AdminAuth::RESET_MINUTES ?> minutes).</p>
      <button class="btn btn--neutre" type="submit">Recevoir un lien par e-mail</button>
    </form>
  </section>

  <section class="adm-carte adm-carte--simple securite-bloc" aria-labelledby="s-activite">
    <h2 class="adm-carte__titre" id="s-activite">Activité récente</h2>
    <?php if ($log === []): ?><p class="adm-discret">Aucune activité enregistrée.</p><?php else: ?>
    <ul class="securite-journal">
      <?php foreach ($log as $l): ?>
        <li><time datetime="<?= e(date('c', (int) strtotime((string) $l['created_at']))) ?>"><?= e(date('d/m/Y H:i', (int) strtotime((string) $l['created_at']))) ?></time>
          <span><?= e($labels[$l['action']] ?? (string) $l['action']) ?><?= $l['target'] !== '' ? ' : ' . e((string) $l['target']) : '' ?></span>
          <span class="securite-ip"><?= e((string) $l['ip']) ?></span></li>
      <?php endforeach ?>
    </ul>
    <?php endif ?>
  </section>

  <form class="adm-rangee" method="post" action="<?= e(A::url('deconnexion/')) ?>"><?= Csrf::field() ?><button class="btn btn--neutre" type="submit">Se déconnecter</button></form>
</div>
