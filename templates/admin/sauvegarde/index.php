<?php
/**
 * Écran Sauvegardes du back-office (gabarit admin/layout du socle, primitives .adm-* de la coquille).
 * Variables : $sauvegardes, $verification, $alertes [[ton, texte]], $chiffree, $distante, $retention [jours, mois],
 * $actions (Lancer, Vérifier), $restauration (Restaurer).
 */

use App\Admin\Admin;
use App\Core\Csrf;
use App\Sauvegarde\Sauvegarde;

$quand = static function (string $date): string {
    $t = (int) strtotime($date);
    $jour = date('Y-m-d', $t);
    $prefixe = match ($jour) {
        date('Y-m-d') => 'Aujourd\'hui',
        date('Y-m-d', strtotime('-1 day')) => 'Hier',
        default => date('d/m/Y', $t),
    };
    return $prefixe . ', ' . date('H:i', $t);
};
$types = ['quotidienne' => 'Quotidienne', 'manuelle' => 'Manuelle', 'avant-restauration' => 'Avant restauration', 'avant-mise-a-jour' => 'Avant mise à jour'];
$ico = [
    'succes' => '<path d="m5 12 5 5 9-10"/>',
    'erreur' => '<path d="M6 6l12 12M18 6 6 18"/>',
    'attention' => '<path d="M12 3 2 20h20zM12 10v4M12 17h.01"/>',
];
$pastille = static fn (string $ton, string $texte): string => '<span class="pastille pastille--' . $ton . '"><svg viewBox="0 0 24 24" width="1em" height="1em" fill="none" stroke="currentColor" stroke-width="3" aria-hidden="true" focusable="false">'
    . $ico[$ton] . '</svg>' . e($texte) . '</span>';
$derniere = $sauvegardes[0] ?? null;
$total = array_sum(array_column($sauvegardes, 'taille'));
?>
<div class="adm-page svc">
  <section class="adm-carte svc-carte" aria-labelledby="svc-titre">
    <div class="adm-carte__entete svc-entete">
      <div class="svc-entete__textes">
        <h1 class="adm-carte__titre" id="svc-titre">Sauvegardes</h1>
        <p class="svc-entete__aide">Chiffrées chaque nuit, gardées <?= (int) $retention[0] ?>&nbsp;jours puis une par mois pendant <?= (int) $retention[1] ?>&nbsp;mois.</p>
      </div>
      <?php if ($actions): ?>
        <form class="svc-entete__action" method="post" action="<?= e(Admin::url('sauvegardes/lancer/')) ?>">
          <?= Csrf::field('sauvegarde') ?>
          <button class="btn" type="submit"><svg class="adm-ico" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M20 12a8 8 0 1 1-2.34-5.66M20 4v4h-4"/></svg>Lancer maintenant</button>
        </form>
      <?php endif; ?>
    </div>

    <?php if ($alertes !== []): ?>
      <div class="svc-alertes adm-pile">
        <?php foreach ($alertes as [$ton, $texte]): ?>
          <div class="adm-alerte adm-alerte--<?= $ton === 'erreur' ? 'erreur' : 'attention' ?>" role="<?= $ton === 'erreur' ? 'alert' : 'status' ?>">
            <svg class="adm-ico" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M12 3a9 9 0 1 0 0 18 9 9 0 0 0 0-18zM12 7.5v5.5M12 16.5v.01"/></svg>
            <p class="adm-alerte__texte"><?= e($texte) ?></p>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <dl class="svc-etat">
      <div class="svc-etat__item">
        <dt>Dernière sauvegarde</dt>
        <dd class="svc-etat__valeur"><?= $derniere ? e($quand($derniere['date'])) : 'Aucune' ?></dd>
        <dd class="svc-etat__sous"><?= $derniere ? e(Sauvegarde::tailleLisible($derniere['taille'])) . ', ' . ($derniere['chiffre'] ? 'chiffrée' : 'NON chiffrée') : 'Programmez la tâche planifiée' ?></dd>
      </div>
      <div class="svc-etat__item">
        <dt>Vérification</dt>
        <dd class="svc-etat__valeur"><?= $verification === null ? $pastille('attention', 'Jamais faite') : ($verification['ok'] ? $pastille('succes', 'Vérifiée') : $pastille('erreur', 'Échec')) ?></dd>
        <dd class="svc-etat__sous"><?= $verification ? e($quand($verification['date'])) : 'php scripts/sauvegarde.php verifier' ?></dd>
      </div>
      <div class="svc-etat__item">
        <dt>Copie hors serveur</dt>
        <dd class="svc-etat__valeur"><?= $distante ? $pastille('succes', 'Configurée') : $pastille('attention', 'Non configurée') ?></dd>
        <dd class="svc-etat__sous"><?= $distante ? 'Après chaque sauvegarde de la nuit' : 'Variable BACKUP_REMOTE du .env' ?></dd>
      </div>
    </dl>

    <?php if ($sauvegardes === []): ?>
      <p class="adm-carte__vide">Aucune sauvegarde pour l'instant.</p>
    <?php else: ?>
      <div class="adm-tableau-cadre">
        <table class="adm-tableau adm-tableau--empile svc-tableau">
          <caption class="adm-masque">Sauvegardes présentes sur le serveur, de la plus récente à la plus ancienne</caption>
          <thead>
            <tr><th scope="col">Date</th><th scope="col">Type</th><th scope="col" class="adm-num">Taille</th><th scope="col">Chiffrement</th><?php if ($restauration): ?><th scope="col" class="adm-col-actions"><span class="adm-masque">Action</span></th><?php endif; ?></tr>
          </thead>
          <tbody>
            <?php foreach ($sauvegardes as $s):
                $date = date('d/m/Y H:i', (int) strtotime($s['date']));
                $type = $types[$s['type']] ?? ucfirst(str_replace('-', ' ', $s['type'])); ?>
              <tr>
                <th scope="row" class="svc-date"><?= e($date) ?></th>
                <td data-label="Type"><?= e($type) ?></td>
                <td data-label="Taille" class="adm-num"><?= e(Sauvegarde::tailleLisible($s['taille'])) ?></td>
                <td data-label="Chiffrement"><?= $s['chiffre']
                    ? '<span class="svc-chiffre"><svg class="adm-ico" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M6 11h12v9H6zM8.5 11V8a3.5 3.5 0 0 1 7 0v3"/></svg>Chiffrée</span>'
                    : $pastille('erreur', 'Non chiffrée') ?></td>
                <?php if ($restauration): ?>
                  <td class="adm-col-actions"><a class="btn btn--neutre btn--petit" href="<?= e(Admin::url('sauvegardes/restaurer/') . '?fichier=' . rawurlencode($s['nom'])) ?>" data-svc-restaurer="<?= e($s['nom']) ?>" data-svc-libelle="<?= e($date . ', ' . mb_strtolower($type)) ?>">Restaurer<span class="adm-masque"> la sauvegarde du <?= e($date) ?></span></a></td>
                <?php endif; ?>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>

    <div class="adm-carte__pied svc-pied">
      <p class="svc-pied__texte"><?= count($sauvegardes) ?> sauvegarde(s), <?= e(Sauvegarde::tailleLisible($total)) ?>. La clé BACKUP_KEY est gardée hors du serveur ; aucun téléchargement par le web.</p>
      <?php if ($actions && $sauvegardes !== []): ?>
        <form method="post" action="<?= e(Admin::url('sauvegardes/verifier/')) ?>">
          <?= Csrf::field('sauvegarde') ?>
          <button class="btn btn--neutre" type="submit">Vérifier la plus récente</button>
        </form>
      <?php endif; ?>
    </div>
  </section>
</div>

<?php if ($restauration): ?>
<dialog class="dialogue" id="svc-dlg" aria-labelledby="svc-dlg-titre" aria-describedby="svc-dlg-texte">
  <form class="dialogue__form" method="post" action="<?= e(Admin::url('sauvegardes/restaurer/')) ?>">
    <?= Csrf::field('sauvegarde') ?>
    <input type="hidden" name="fichier" value="" data-svc-fichier>
    <div class="dialogue__entete">
      <h2 class="dialogue__titre" id="svc-dlg-titre">Restaurer cette sauvegarde&#8239;?</h2>
      <button class="dialogue__fermer" type="button" data-svc-fermer><svg class="adm-ico" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M6 6l12 12M18 6 6 18"/></svg><span class="adm-masque">Fermer</span></button>
    </div>
    <div class="dialogue__corps">
      <p id="svc-dlg-texte">La base actuelle sera remplacée. Tout ce qui a été saisi depuis cette date sera perdu ; une sauvegarde de sécurité est faite juste avant.</p>
      <dl class="dialogue__objet"><dt>Sauvegarde</dt><dd data-svc-objet>&nbsp;</dd></dl>
      <div class="adm-champ">
        <label class="adm-champ__libelle" for="svc-dlg-saisie">Pour confirmer, saisissez <strong>RESTAURER</strong></label>
        <p class="adm-champ__aide" id="svc-dlg-aide">Le bouton s'active quand le mot est exact.</p>
        <input class="adm-saisie" id="svc-dlg-saisie" type="text" name="confirmation" autocomplete="off" spellcheck="false" autofocus aria-describedby="svc-dlg-aide" data-svc-saisie>
      </div>
    </div>
    <div class="dialogue__actions">
      <button class="btn btn--neutre" type="button" data-svc-fermer>Annuler</button>
      <button class="btn btn--danger" type="submit" disabled data-svc-valider>Restaurer</button>
    </div>
  </form>
</dialog>
<?php endif; ?>
