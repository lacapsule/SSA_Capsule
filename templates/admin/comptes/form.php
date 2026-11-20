<?php
/**
 * Ajout ou modification d'un compte.
 * @var ?array $account @var bool $own @var array<string, mixed> $values @var array<string, string> $errors
 * @var bool $roles @var array<string, string> $perimeters
 */
use App\Admin\AccountController;
use App\Admin\Admin;
use App\Admin\Ui;
use App\Core\Csrf;

$aria = static function (string $name, string $hint = '') use ($errors): string {
    $ids = trim($hint . (isset($errors[$name]) ? " $name-error" : ''));
    return ($ids !== '' ? ' aria-describedby="' . $ids . '"' : '') . (isset($errors[$name]) ? ' aria-invalid="true"' : '');
};
$error = static fn (string $name): string => isset($errors[$name]) ? '<p class="adm-champ__erreur" id="' . $name . '-error">Erreur : ' . e($errors[$name]) . '</p>' : '';
$role = ($values['role'] ?? 'editor') === 'admin' ? 'admin' : 'editor';
$perimetre = (string) ($values['perimetre'] ?? '');
$heading = $account ? ($account['name'] !== '' ? $account['name'] : $account['email']) . ($own ? ' (vous)' : '') : 'Nouveau compte';
$list = Admin::url('comptes/');
?>
<header class="adm-entete-page">
  <div class="adm-entete-page__textes">
    <a class="adm-retour" href="<?= e($list) ?>"><?= Ui::icon('back') ?>Comptes</a>
    <h1 class="adm-entete-page__titre"><?= e($heading) ?></h1>
  </div>
</header>
<div data-modal-content data-modal-title="<?= e($heading) ?>">
<?php if ($errors !== []): ?>
<div class="adm-alerte adm-alerte--erreur" role="alert"><?= Ui::icon('alert') ?><p><strong>Erreur :</strong> rien n'a été enregistré. Corrigez les champs signalés.</p></div>
<?php endif ?>
<?php /* Une colonne à la largeur de ses champs ; Enregistrer et Annuler dans le pied de la fenêtre (data-modal-foot), mot de passe en une ligne : jamais saisi ici, seulement par lien (2026-11-20). */ ?>
<form class="adm-carte adm-carte--simple compte-fiche" id="compte" method="post" action="<?= e($account ? Admin::url('comptes/' . $account['id'] . '/') : $list) ?>" novalidate>
  <?= Csrf::field('compte') ?>
  <div class="adm-grille-champs adm-grille-champs--2">
    <div class="adm-champ adm-champ--pile">
      <div class="adm-champ__tete">
        <label for="name">Prénom et nom <span class="adm-champ__requis">(obligatoire)</span></label>
        <?= $error('name') ?>
      </div>
      <input id="name" name="name" autocomplete="off" required maxlength="120" value="<?= e((string) ($values['name'] ?? '')) ?>"<?= $aria('name') ?>>
    </div>
    <div class="adm-champ adm-champ--pile">
      <div class="adm-champ__tete">
        <label for="email">E-mail <span class="adm-champ__requis">(obligatoire)</span></label>
        <?= $error('email') ?>
      </div>
      <input id="email" name="email" type="email" autocomplete="off" required maxlength="190" value="<?= e((string) ($values['email'] ?? '')) ?>"<?= $aria('email', 'email-hint') ?>>
    </div>
    <p class="adm-champ__aide compte-fiche__aide" id="email-hint"><?= $account ? 'L\'e-mail sert à se connecter et à recevoir les liens de mot de passe.' : 'Une invitation est envoyée à cet e-mail, valable ' . AccountController::INVITE_DAYS . ' jours : la personne choisit elle-même son mot de passe.' ?></p>
    <?php if ($roles): ?>
    <div class="adm-champ adm-champ--pile">
      <div class="adm-champ__tete">
        <label for="role">Rôle</label>
        <?= $error('role') ?>
      </div>
      <select id="role" name="role" data-role-select<?= $aria('role', 'role-hint') ?>>
        <option value="editor"<?= $role === 'editor' ? ' selected' : '' ?>>Éditeur</option>
        <option value="admin"<?= $role === 'admin' ? ' selected' : '' ?>>Administrateur</option>
      </select>
    </div>
    <?php if ($perimeters !== []): ?>
    <div class="adm-champ adm-champ--pile">
      <div class="adm-champ__tete">
        <label for="perimetre">Périmètre (éditeur)</label>
        <?= $error('perimetre') ?>
      </div>
      <select id="perimetre" name="perimetre" data-role-org<?= $role === 'admin' ? ' disabled' : '' ?><?= $aria('perimetre', 'role-hint') ?>>
        <option value="">Tout le contenu</option>
        <?php foreach ($perimeters as $key => $label): ?>
        <option value="<?= e((string) $key) ?>"<?= $perimetre === (string) $key ? ' selected' : '' ?>><?= e($label) ?></option>
        <?php endforeach ?>
      </select>
    </div>
    <?php endif ?>
    <p class="adm-champ__aide compte-fiche__aide" id="role-hint">Administrateur : tout le site, les comptes et le journal. Éditeur : les contenus<?= $perimeters !== [] ? ', dans son périmètre s\'il en a un' : '' ?>.</p>
    <?php endif ?>
  </div>
  <?php if ($account): ?>
  <div class="securite-ligne compte-fiche__mdp">
    <p class="securite-ligne__texte"><strong>Mot de passe</strong> : jamais saisi ici. <?= $own ? 'Vous recevez' : 'La personne reçoit' ?> un lien pour en choisir un nouveau ; l'actuel reste valable en attendant.</p>
    <button class="btn btn--neutre" type="submit" form="lien-mot-de-passe"><?= Ui::icon('send') ?><?= $account['pending'] ? 'Renvoyer l\'invitation' : 'Envoyer un lien' ?></button>
  </div>
  <?php endif ?>
</form>
<div class="adm-pied-form" data-modal-foot>
  <button class="btn" type="submit" form="compte"><?= $account ? 'Enregistrer' : 'Créer et envoyer l\'invitation' ?></button>
  <a class="btn btn--neutre" href="<?= e($list) ?>" data-modal-close>Annuler</a>
</div>
<?php if ($account): ?>
<form id="lien-mot-de-passe" method="post" action="<?= e(Admin::url('comptes/' . $account['id'] . '/lien/')) ?>" hidden><?= Csrf::field() ?></form>
<?php endif ?>
</div>
