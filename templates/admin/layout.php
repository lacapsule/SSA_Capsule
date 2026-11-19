<?php
/**
 * Coque du tableau de bord des modules PHP, posée sur la coquille de référence (gabarits/admin-coquille) :
 * mêmes classes .adm-*, même feuille admin-coquille.css, même script admin-coquille.js. Ce fichier ne fait que
 * préparer les variables de templates/admin/coquille.php (menu filtré par rôle, compte, messages flash) et ajouter
 * les fenêtres dialog du gabarit (modification, aperçu, confirmation) que pilote admin.js.
 * Sans compte connecté : écran de connexion de sections/admin-connexion (classes .cnx-*).
 * Indépendante de la garde : compte lu par Admin::current() (contrat des modules du socle PHP), rôle et périmètre par
 * les clés communes, libellé du périmètre par roles-et-journal s'il est là.
 * @var string $title @var string $content ; facultatif : list<string> $styles et $scripts (feuilles et scripts des modules),
 * list<array{libelle: string, url?: string}> $fil (fil d'Ariane ; par défaut Tableau de bord puis le titre).
 */
use App\Admin\Admin;
use App\Admin\Ui;
use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Session;

$user = Admin::current();
$flashes = Session::takeFlashes();
$appName = (string) ($_ENV['APP_NAME'] ?? config('app.name', 'Site'));
$asset = static fn (string $path): string => $path . (is_file(ROOT . '/public' . $path) ? '?v=' . filemtime(ROOT . '/public' . $path) : '');
// Feuilles et scripts communs : tokens, composants (bouton, pastille-statut, etat-vide-chargement, champ-mot-de-passe),
// coquille de référence, puis la couche propre au gabarit (admin.css : fenêtres, onglets dynamiques, aperçu).
$feuilles = array_map($asset, ['/assets/css/tokens.css', '/assets/css/charte.css', '/assets/css/composants.css', '/assets/css/admin-coquille.css', '/assets/css/admin.css']);
$modules = array_map(static fn ($h): string => $asset((string) $h), isset($styles) && is_array($styles) ? $styles : []);
$scriptsCommuns = array_map($asset, ['/assets/js/admin-coquille.js', '/assets/js/mot-de-passe.js', '/assets/js/admin.js']);
$scriptsModules = isset($scripts) && is_array($scripts) ? $scripts : [];

if ($user === null):
    // Écran de connexion (sections/admin-connexion) : la carte reçoit la vue du module d'authentification.
?><!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?= e($title) ?> · Administration <?= e($appName) ?></title>
<?php foreach ([...$feuilles, $asset('/assets/css/admin-connexion.css'), $asset('/assets/css/admin-ssa.css'), ...$modules] as $href): ?><link rel="stylesheet" href="<?= e($href) ?>">
<?php endforeach ?>
<?php foreach ($scriptsCommuns as $src): ?><script src="<?= e($src) ?>" defer></script>
<?php endforeach ?>
</head>
<body class="adm-corps">
<div class="adm cnx">
  <div class="cnx__colonne">
    <header class="cnx__entete">
      <div class="cnx-marque"><span class="cnx-marque__logo"><img src="/assets/img/logo-entete-112.webp" alt="" width="56" height="58"></span><span class="cnx-marque__texte"><span class="cnx-marque__nom"><?= e($appName) ?></span><span class="cnx-marque__sous">Administration</span></span></div>
    </header>
    <main class="cnx__principal" id="contenu" tabindex="-1">
      <div class="adm-carte cnx-carte">
        <?php foreach ($flashes as $flash): $ton = match ($flash['type']) { 'error', 'erreur', 'danger' => 'erreur', 'warning', 'attention' => 'attention', 'info' => 'info', default => 'succes' }; ?>
        <p class="adm-alerte<?= $ton !== 'info' ? ' adm-alerte--' . $ton : '' ?>" role="<?= $ton === 'erreur' ? 'alert' : 'status' ?>"><?= e($flash['message']) ?></p>
        <?php endforeach ?>
        <?= $content ?>
      </div>
    </main>
    <footer class="cnx__pied">
      <a href="/"><?= Ui::icon('back') ?>Retour au site</a>
    </footer>
  </div>
</div>
</body>
</html>
<?php
    return;
endif;

// Menu : entrées de config/admin-menu.php (format de la coquille : groupes et entrées), filtrées par rôle par la coquille.
$isAdmin = $user['role'] === 'admin';
$perimeters = class_exists(Auth::class) ? Auth::perimeters() : [];
$roleLibelle = $isAdmin ? 'Administrateur' : 'Éditeur' . ($user['perimetre'] !== null ? ' · ' . ($perimeters[$user['perimetre']] ?? $user['perimetre']) : '');
$home = Admin::url();
$menu = is_file(ROOT . '/config/admin-menu.php') ? (array) require ROOT . '/config/admin-menu.php' : [];

// Les fenêtres du gabarit (admin.js) : fragment de page en dialog, aperçu ordinateur ou mobile, confirmation.
ob_start(); ?>
<p class="adm-masque" id="admin-live" role="status" aria-live="polite"></p>
<dialog class="adm adm-fenetre" id="modal" aria-labelledby="modal-title">
  <div class="adm-fenetre__entete">
    <h2 class="adm-fenetre__titre" id="modal-title"></h2>
    <button class="adm-icone-btn" type="button" data-modal-close><?= Ui::icon('close') ?><span class="adm-masque">Fermer</span></button>
  </div>
  <div class="adm-fenetre__corps" data-modal-body tabindex="-1"></div>
  <div class="adm-fenetre__pied" data-modal-foot-slot hidden></div>
</dialog>
<dialog class="adm adm-fenetre adm-fenetre--apercu" id="preview-dialog" aria-labelledby="preview-title">
  <div class="adm-fenetre__entete">
    <h2 class="adm-fenetre__titre" id="preview-title">Aperçu</h2>
    <div class="adm-bascule" role="group" aria-label="Largeur de l'aperçu">
      <button class="adm-bascule__option" type="button" data-preview-width="desktop" aria-pressed="true"><?= Ui::icon('monitor') ?>Ordinateur</button>
      <button class="adm-bascule__option" type="button" data-preview-width="mobile" aria-pressed="false"><?= Ui::icon('phone') ?>Mobile</button>
    </div>
    <button class="adm-icone-btn" type="button" data-preview-close><?= Ui::icon('close') ?><span class="adm-masque">Fermer l'aperçu</span></button>
  </div>
  <div class="adm-fenetre__corps adm-apercu-scene">
    <iframe class="adm-apercu-cadre" title="Aperçu" sandbox="" data-preview-frame></iframe>
  </div>
</dialog>
<dialog class="adm adm-confirmation" id="confirm-dialog" aria-labelledby="confirm-text">
  <p id="confirm-text"></p>
  <div class="adm-rangee adm-rangee--fin">
    <button class="btn btn--neutre" type="button" data-confirm-cancel>Annuler</button>
    <button class="btn" type="button" data-confirm-ok>Confirmer</button>
  </div>
</dialog>
<?php
$apresContenu = (string) ob_get_clean();

// La coquille de référence rend la page ; le contenu des modules est posé dans .adm-page (espacement commun).
$titre = (string) $title;
// Compte repris de l'ancien site sans double authentification (décision du 2026-11-19) : rappel sur chaque page jusqu'à
// l'activation, avec l'échéance après laquelle elle sera demandée à la connexion.
$rappel2fa = '';
$echeance2fa = ($user['totp_enabled_at'] ?? null) === null ? App\Admin\AdminAuth::delaiReprise($user) : null;
if ($echeance2fa !== null) {
    $rappel2fa = '<div class="adm-alerte adm-alerte--attention" role="status"><p class="adm-alerte__texte">'
        . '<strong>Activez la double authentification</strong> : elle protège le site même si votre mot de passe est volé. '
        . 'Elle sera demandée à la connexion à partir du ' . e(App\Site\Dates::courte($echeance2fa)) . '. '
        . '<a href="' . e(Admin::url('securite/')) . '">L\'activer maintenant</a></p></div>';
}
$contenu = '<div class="adm-page">' . $rappel2fa . $content . '</div>';
$fil = isset($fil) && is_array($fil) ? $fil : array_values(array_filter([
    ['libelle' => 'Tableau de bord', 'url' => $home],
    $titre !== 'Tableau de bord' ? ['libelle' => $titre] : null,
]));
$utilisateur = ['nom' => (string) ($user['name'] ?: $user['email']), 'role' => (string) $user['role'], 'role_libelle' => $roleLibelle];
$csrf = Csrf::token();
$accueil = $home;
$creer = [];
$recherche = null;
$notifs = false;
$compteLiens = is_file(ROOT . '/templates/admin/settings-securite.php') ? [['url' => Admin::url('securite/'), 'libelle' => 'Sécurité du compte', 'icone' => 'cle']] : [];
$deconnexion = Admin::guard()->logoutUrl();
$messages = array_map(static fn (array $f): array => ['type' => (string) $f['type'], 'texte' => (string) $f['message']], $flashes);
// La coquille charge déjà tokens.css, composants.css, admin-coquille.css et admin-coquille.js : seulement le reste ici.
$styles = [$asset('/assets/css/admin.css'), ...$modules];
$scripts = [$asset('/assets/js/mot-de-passe.js'), $asset('/assets/js/admin.js')];
foreach ($scriptsModules as $src) {
    // Un script de module ES s'écrit ['src' => ..., 'module' => true].
    $scripts[] = is_array($src) ? ['src' => $asset((string) $src['src']), 'module' => !empty($src['module'])] : $asset((string) $src);
}
require __DIR__ . '/coquille.php';
