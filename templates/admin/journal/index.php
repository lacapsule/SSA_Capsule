<?php
/**
 * @var list<array<string, mixed>> $rows @var int $total @var int $page @var int $pages
 * @var string $account @var string $action @var list<array<string, mixed>> $accounts
 * @var list<string> $actions @var list<string> $families
 */
use App\Admin\Admin;
use App\Admin\AuditController;
use App\Admin\Ui;

$filtered = $account !== '' || $action !== '';
$first = ($page - 1) * AuditController::PER_PAGE + 1;
$last = min($total, $page * AuditController::PER_PAGE);
?>
<header class="adm-entete-page">
  <div class="adm-entete-page__textes">
    <a class="adm-retour" href="<?= e(Admin::url('comptes/')) ?>"><?= Ui::icon('back') ?>Comptes</a>
    <h1 class="adm-entete-page__titre">Journal</h1>
    <p class="adm-entete-page__sous-titre">Qui a fait quoi sur le tableau de bord, du plus récent au plus ancien. Les lignes de plus d'un an sont effacées.</p>
  </div>
</header>

<section class="adm-carte" aria-labelledby="journal-titre">
  <h2 class="adm-masque" id="journal-titre">Actions enregistrées</h2>
  <form class="adm-filtres" method="get" action="<?= e(Admin::url('journal/')) ?>">
    <div class="adm-champ adm-champ--pile">
      <label for="j-compte">Compte</label>
      <select id="j-compte" name="compte">
        <option value="">Tous les comptes</option>
        <?php foreach ($accounts as $a): ?>
        <option value="<?= (int) $a['id'] ?>"<?= $account === (string) $a['id'] ? ' selected' : '' ?>><?= e((string) ($a['name'] ?: $a['email'])) ?></option>
        <?php endforeach ?>
        <option value="aucun"<?= $account === 'aucun' ? ' selected' : '' ?>>Sans compte connecté</option>
      </select>
    </div>
    <div class="adm-champ adm-champ--pile">
      <label for="j-action">Action</label>
      <select id="j-action" name="action">
        <option value="">Toutes les actions</option>
        <?php if ($families !== []): ?>
        <optgroup label="Familles">
          <?php foreach ($families as $f): ?>
          <option value="<?= e($f) ?>"<?= $action === $f ? ' selected' : '' ?>><?= e(substr($f, 0, -2)) ?> (toutes)</option>
          <?php endforeach ?>
        </optgroup>
        <?php endif ?>
        <optgroup label="Actions">
          <?php foreach ($actions as $a): ?>
          <option value="<?= e($a) ?>"<?= $action === $a ? ' selected' : '' ?>><?= e(AuditController::label($a)) ?></option>
          <?php endforeach ?>
        </optgroup>
      </select>
    </div>
    <div class="adm-rangee">
      <button class="btn" type="submit"><?= Ui::icon('search') ?>Filtrer</button>
      <?php if ($filtered): ?><a class="btn btn--discret" href="<?= e(Admin::url('journal/')) ?>"><?= Ui::icon('close') ?>Effacer les filtres</a><?php endif ?>
    </div>
    <p class="adm-filtres__compte" role="status"><?= $total === 0 ? 'Aucune ligne' : ($total === 1 ? '1 ligne' : "$first à $last sur $total lignes") ?></p>
  </form>

  <?php if ($rows === []): ?>
  <div class="adm-etat adm-etat--compact"><p class="adm-etat__titre">Aucune action</p><p class="adm-etat__texte"><?= $filtered ? 'Aucune ligne ne correspond à ces filtres.' : 'Le journal est vide pour l\'instant.' ?></p></div>
  <?php else: ?>
  <div class="adm-tableau-cadre">
    <table class="adm-tableau adm-tableau--empile">
      <caption class="adm-masque">Journal, page <?= $page ?> sur <?= $pages ?></caption>
      <thead><tr><th scope="col">Date</th><th scope="col">Compte</th><th scope="col">Action</th><th scope="col">Objet</th><th scope="col">Adresse IP</th></tr></thead>
      <tbody>
        <?php foreach ($rows as $r): ?>
        <tr>
          <th scope="row" class="adm-insecable"><time datetime="<?= e(str_replace(' ', 'T', (string) $r['created_at'])) ?>Z"><?= e(Ui::date((string) $r['created_at'], true)) ?></time></th>
          <td data-label="Compte"><?= $r['email'] !== null ? e((string) ($r['name'] ?: $r['email'])) : ($r['actor'] !== null && $r['actor'] !== '' ? e((string) $r['actor']) . ' <span class="adm-discret">(compte supprimé)</span>' : '<span class="adm-discret">Sans compte</span>') ?></td>
          <td data-label="Action"><?= e(AuditController::label((string) $r['action'])) ?></td>
          <td data-label="Objet" class="adm-extrait"><?= $r['target'] !== null && $r['target'] !== '' ? e((string) $r['target']) : '<span class="adm-discret">Aucun</span>' ?></td>
          <td data-label="Adresse IP" class="adm-insecable"><?= e((string) $r['ip']) ?></td>
        </tr>
        <?php endforeach ?>
      </tbody>
    </table>
  </div>
  <?php endif ?>
</section>

<?php if ($pages > 1): ?>
<nav class="adm-pages" aria-label="Pages du journal">
  <?php if ($page > 1): ?>
  <a class="btn btn--neutre" href="<?= e(AuditController::pageUrl($page - 1, $account, $action)) ?>" rel="prev"><?= Ui::icon('back') ?>Plus récentes</a>
  <?php endif ?>
  <p class="adm-pages__etat">Page <?= $page ?> sur <?= $pages ?></p>
  <?php if ($page < $pages): ?>
  <a class="btn btn--neutre" href="<?= e(AuditController::pageUrl($page + 1, $account, $action)) ?>" rel="next">Plus anciennes<?= Ui::icon('arrow') ?></a>
  <?php endif ?>
</nav>
<?php endif ?>
