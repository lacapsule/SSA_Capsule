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
$initiales = static function (string $nom): string {
    $i = '';
    foreach (array_slice(preg_split('/\s+/u', trim($nom)) ?: [], 0, 2) as $m) {
        $i .= mb_strtoupper(mb_substr($m, 0, 1));
    }
    return $i;
};
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
<?php /* Une ligne par personne, comme les autres listes (2026-11-20) : le tableau coupait l'e-mail et le rôle sous 64 em. */ ?>
<section class="adm-carte adm-liste" aria-labelledby="comptes-titre">
  <div class="adm-carte__entete"><h2 class="adm-carte__titre" id="comptes-titre"><?= e(Ui::count(count($accounts), 'personne', 'personnes')) ?></h2></div>
  <ul class="adm-lignes comptes-liste" role="list">
    <?php foreach ($accounts as $a): $self = $me !== null && $a['id'] === (int) $me['id']; $name = $a['name'] !== '' ? $a['name'] : $a['email']; $url = Admin::url('comptes/' . $a['id'] . '/'); ?>
    <li>
      <span class="adm-avatar comptes-liste__avatar" aria-hidden="true"><?= e($a['name'] !== '' ? $initiales($name) : mb_strtoupper(mb_substr($name, 0, 1))) ?></span>
      <a class="adm-lignes__principal" href="<?= e($url) ?>" data-modal>
        <span class="adm-lignes__titre"><?= e($name) ?><?= $self ? ' <span class="comptes-liste__vous">(vous)</span>' : '' ?></span>
        <?php $meta = array_filter([$a['name'] !== '' ? $a['email'] : '', $roles ? $access($a) : '']); if ($meta !== []): ?><span class="adm-meta"><?= e(implode(' · ', $meta)) ?></span><?php endif ?>
      </a>
      <span class="comptes-liste__etat">
        <?php if ($a['pending']): ?><span class="pastille pastille--point pastille--attention">Invitation envoyée</span>
        <?php else: ?>
        <span class="adm-meta">Connexion : <?= e(Ui::date($a['last_login_at'] !== null ? (string) $a['last_login_at'] : null, true)) ?></span>
        <?php if (!$a['two_factor']): ?><span class="pastille pastille--point pastille--attention">Sans double authentification</span><?php endif ?>
        <?php endif ?>
      </span>
      <span class="adm-rangee comptes-liste__actions">
        <a class="btn btn--neutre" href="<?= e($url) ?>" data-modal><?= Ui::icon('edit') ?>Modifier<span class="adm-masque"> <?= e($name) ?></span></a>
        <?php if (!$self): ?>
        <form method="post" action="<?= e(Admin::url('comptes/' . $a['id'] . '/suppression/')) ?>" data-confirm="Supprimer le compte de <?= e($name) ?> ? Cette personne ne pourra plus se connecter.">
          <?= Csrf::field() ?>
          <button class="btn btn--danger btn--secondaire" type="submit"><?= Ui::icon('trash') ?>Supprimer<span class="adm-masque"> <?= e($name) ?></span></button>
        </form>
        <?php endif ?>
      </span>
    </li>
    <?php endforeach ?>
  </ul>
</section>
