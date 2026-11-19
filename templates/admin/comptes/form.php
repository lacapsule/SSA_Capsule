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
<form class="adm-carte adm-carte--simple" method="post" action="<?= e($account ? Admin::url('comptes/' . $account['id'] . '/') : $list) ?>" novalidate>
  <?= Csrf::field('compte') ?>
  <h2 class="adm-carte__titre">La personne</h2>
  <div class="adm-grille-champs adm-grille-champs--2">
    <div class="adm-champ adm-champ--pile">
      <label for="name">Prénom et nom <span class="adm-champ__requis">(obligatoire)</span></label>
      <?= $error('name') ?>
      <input id="name" name="name" autocomplete="off" required maxlength="120" value="<?= e((string) ($values['name'] ?? '')) ?>"<?= $aria('name') ?>>
    </div>
    <div class="adm-champ adm-champ--pile">
      <label for="email">E-mail <span class="adm-champ__requis">(obligatoire)</span></label>
      <p class="adm-champ__aide" id="email-hint"><?= $account ? 'Sert à se connecter et à recevoir les liens de mot de passe.' : 'Une invitation y est envoyée, valable ' . AccountController::INVITE_DAYS . ' jours : la personne choisit elle-même son mot de passe.' ?></p>
      <?= $error('email') ?>
      <input id="email" name="email" type="email" autocomplete="off" required maxlength="190" value="<?= e((string) ($values['email'] ?? '')) ?>"<?= $aria('email', 'email-hint') ?>>
    </div>
    <?php if ($roles): ?>
    <div class="adm-champ adm-champ--pile">
      <label for="role">Rôle</label>
      <p class="adm-champ__aide" id="role-hint">Administrateur : tout le site, les comptes et le journal. Éditeur : les contenus, dans son périmètre s'il en a un.</p>
      <?= $error('role') ?>
      <select id="role" name="role" data-role-select<?= $aria('role', 'role-hint') ?>>
        <option value="editor"<?= $role === 'editor' ? ' selected' : '' ?>>Éditeur</option>
        <option value="admin"<?= $role === 'admin' ? ' selected' : '' ?>>Administrateur</option>
      </select>
    </div>
    <?php if ($perimeters !== []): ?>
    <div class="adm-champ adm-champ--pile">
      <label for="perimetre">Périmètre de l'éditeur</label>
      <p class="adm-champ__aide" id="perimetre-hint">Seulement pour un éditeur : il ne voit et ne modifie que les contenus de ce périmètre.</p>
      <?= $error('perimetre') ?>
      <select id="perimetre" name="perimetre" data-role-org<?= $role === 'admin' ? ' disabled' : '' ?><?= $aria('perimetre', 'perimetre-hint') ?>>
        <option value="">Tout le contenu</option>
        <?php foreach ($perimeters as $key => $label): ?>
        <option value="<?= e((string) $key) ?>"<?= $perimetre === (string) $key ? ' selected' : '' ?>><?= e($label) ?></option>
        <?php endforeach ?>
      </select>
    </div>
    <?php endif ?>
    <?php endif ?>
  </div>

  <?php if ($account): ?>
  <h2 class="adm-carte__titre adm-carte__titre-separe" id="mot-de-passe">Mot de passe</h2>
  <p class="adm-carte__intro">Le mot de passe n'est jamais saisi ici : <?= $own ? 'vous recevez' : 'la personne reçoit' ?> un lien à <?= e($account['email']) ?> pour choisir le nouveau. L'actuel reste valable en attendant.<?= $own ? ' Pour le changer vous-même, passez par la page Sécurité.' : '' ?></p>
  <p><button class="btn btn--neutre" type="submit" form="lien-mot-de-passe"><?= Ui::icon('send') ?><?= $account['pending'] ? 'Renvoyer l\'invitation' : 'Envoyer un lien de changement' ?></button></p>
  <?php endif ?>
  <div class="adm-pied-form">
    <button class="btn" type="submit"><?= $account ? 'Enregistrer' : 'Créer et envoyer l\'invitation' ?></button>
    <a class="btn btn--neutre" href="<?= e($list) ?>" data-modal-close>Annuler</a>
  </div>
</form>
<?php if ($account): ?>
<form id="lien-mot-de-passe" method="post" action="<?= e(Admin::url('comptes/' . $account['id'] . '/lien/')) ?>" hidden><?= Csrf::field() ?></form>
<?php endif ?>
</div>
