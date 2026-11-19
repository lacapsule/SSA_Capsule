<?php
/** Liste des comptes. @var list<array<string, mixed>> $accounts */
use App\Admin\AccountController;
use App\Admin\Admin;
use App\Admin\Ui;
use App\Core\Auth;
use App\Core\Csrf;

$me = Admin::current();
$roles = AccountController::hasRoles();
$perimeters = $roles ? Auth::perimeters() : [];
$access = static fn (array $a): string => $a['role'] === 'admin'
    ? 'Administrateur'
    : 'Éditeur' . ($a['perimetre'] !== null ? ' · ' . ($perimeters[$a['perimetre']] ?? $a['perimetre']) : '');
?>
<header class="adm-entete-page">
  <div class="adm-entete-page__textes">
    <h1 class="adm-entete-page__titre">Comptes</h1>
    <p class="adm-entete-page__sous-titre">Les personnes qui peuvent se connecter au tableau de bord. Une personne ajoutée reçoit une invitation par e-mail et choisit elle-même son mot de passe.</p>
  </div>
  <div class="adm-entete-page__actions">
    <?php if ($roles): ?><a class="btn btn--neutre" href="<?= e(Admin::url('journal/')) ?>"><?= Ui::icon('list') ?>Journal</a><?php endif ?>
    <a class="btn" href="<?= e(Admin::url('comptes/nouveau/')) ?>" data-modal><?= Ui::icon('plus') ?>Ajouter une personne</a>
  </div>
</header>
<section class="adm-carte" aria-label="Liste des comptes">
  <div class="adm-tableau-cadre">
    <table class="adm-tableau adm-tableau--empile">
      <caption class="adm-masque">Comptes d'accès au tableau de bord</caption>
      <thead><tr><th scope="col">Nom</th><th scope="col">E-mail</th><?php if ($roles): ?><th scope="col">Accès</th><?php endif ?><th scope="col">Dernière connexion</th><th scope="col" class="adm-col-actions"><span class="adm-masque">Actions</span></th></tr></thead>
      <tbody>
        <?php foreach ($accounts as $a): $self = $me !== null && $a['id'] === (int) $me['id']; $name = $a['name'] !== '' ? $a['name'] : $a['email']; $url = Admin::url('comptes/' . $a['id'] . '/'); ?>
        <tr>
          <th scope="row"><a href="<?= e($url) ?>" data-modal><?= e($name) ?></a><?= $self ? ' <span class="pastille pastille--point">Vous</span>' : '' ?></th>
          <td data-label="E-mail"><?= e($a['email']) ?></td>
          <?php if ($roles): ?><td data-label="Accès"><?= e($access($a)) ?></td><?php endif ?>
          <td data-label="Connexion">
            <?php if ($a['pending']): ?><span class="pastille pastille--point pastille--attention">Invitation en attente</span>
            <?php else: ?><?= e(Ui::date($a['last_login_at'] !== null ? (string) $a['last_login_at'] : null, true)) ?><?php if (!$a['two_factor']): ?> <span class="pastille pastille--point pastille--attention">Double authentification à activer</span><?php endif ?>
            <?php endif ?>
          </td>
          <td class="adm-col-actions">
            <div class="adm-rangee">
              <a class="btn btn--neutre" href="<?= e($url) ?>" data-modal><?= Ui::icon('edit') ?>Modifier<span class="adm-masque"> <?= e($name) ?></span></a>
              <?php if (!$self): ?>
              <form method="post" action="<?= e(Admin::url('comptes/' . $a['id'] . '/suppression/')) ?>" data-confirm="Supprimer le compte de <?= e($name) ?> ? Cette personne ne pourra plus se connecter.">
                <?= Csrf::field() ?>
                <button class="btn btn--danger btn--secondaire" type="submit"><?= Ui::icon('trash') ?>Supprimer<span class="adm-masque"> <?= e($name) ?></span></button>
              </form>
              <?php endif ?>
            </div>
          </td>
        </tr>
        <?php endforeach ?>
      </tbody>
    </table>
  </div>
</section>
