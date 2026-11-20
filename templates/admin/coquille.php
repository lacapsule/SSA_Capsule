<?php
/**
 * Coquille d'administration : barre latérale par rubriques (repliable en icônes dès 64 em, tiroir en dessous),
 * barre du haut (fil d'Ariane, recherche, menu Créer, notifications, compte), onglets du bas sous 64 em.
 *
 * e() (échappement HTML) est fournie par le socle (gabarits/socle-php) :
 *   function e(string $v): string { return htmlspecialchars($v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
 *
 * Variables attendues :
 * @var string      $titre         titre de la page (balise title)
 * @var string      $contenu       HTML de la page, déjà rendu et échappé (par ex. sections/admin-liste)
 * @var array       $fil           fil d'Ariane : [['libelle' => 'Commandes', 'url' => '/admin/commandes'], ['libelle' => 'CMD-0412']]
 * @var array       $utilisateur   ['nom' => 'Camille Martin', 'role' => 'admin', 'role_libelle' => 'Administratrice']
 * @var array       $notifs        centre de notifications (composants/centre-notifications) : id, page, preferences, url, groupes
 * @var string      $csrf          jeton CSRF (modules/securite-formulaires)
 * @var string      $variante      'laterale' (par défaut) ou 'haut' (barre du haut seule, cinq rubriques au plus)
 *
 * Facultatives (gabarits/tableau-de-bord-php s'en sert pour poser la même coquille sur les modules PHP) :
 * @var string      $accueil       adresse de l'accueil de l'administration (par défaut /admin/)
 * @var array|null  $menu          rubriques au format de config/admin-menu.php (par défaut : ce fichier)
 * @var array       $creer         entrées du menu Créer ([] : menu masqué)
 * @var string|null $recherche     adresse de la page de recherche (null : recherche masquée)
 * @var array|false $notifs        false : pas de cloche (aucun centre de notifications installé)
 * @var array       $compteLiens   liens du menu du compte : [['url' => ..., 'libelle' => ..., 'icone' => ...]]
 * @var string      $deconnexion   adresse du POST de déconnexion
 * @var array       $messages      messages en tête du contenu (flashes) : [['type' => 'succes', 'texte' => '...']],
 *                                 types succes, info, attention, erreur (anciens noms success, warning, error acceptés).
 *                                 Seul le succès disparaît seul après 5 s (script.js) ; les autres restent jusqu'à fermeture.
 * @var list<string> $styles, $scripts  feuilles et scripts ajoutés après ceux de la coquille ; un script de module ES :
 *                                 ['src' => '/assets/js/x.js', 'module' => true]
 * @var string      $apresContenu  HTML de confiance posé après la coquille (fenêtres dialog du gabarit)
 */

$nomSite = (string) ($_ENV['APP_NAME'] ?? config('app.name', 'Site'));
$accueil = (string) ($accueil ?? '/admin/');
$recherche = array_key_exists('recherche', get_defined_vars()) ? $recherche : '/admin/recherche';
$deconnexion = (string) ($deconnexion ?? '/admin/deconnexion');
$messages ??= [];
$styles ??= [];
$scripts ??= [];
$apresContenu = (string) ($apresContenu ?? '');
$variante = ($variante ?? 'laterale') === 'haut' ? 'haut' : 'laterale';
$fil = $fil ?? [];
$notifs ??= ['id' => 'cn', 'page' => '/admin/notifications', 'preferences' => '/admin/compte/notifications', 'url' => '/admin/notifications/lues', 'groupes' => []];
$chemin = (string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
// Menu replié : cookie posé par le serveur si on veut éviter tout saut au chargement (le script garde aussi la préférence).
$replie = ($_COOKIE['adm_menu_replie'] ?? '') === '1';

$creer ??= [
    ['url' => '/admin/commandes/nouvelle', 'libelle' => 'Commande', 'icone' => 'commandes'],
    ['url' => '/admin/membres/nouveau', 'libelle' => 'Membre', 'icone' => 'membres'],
    ['url' => '/admin/evenements/nouveau', 'libelle' => 'Événement', 'icone' => 'evenements'],
    ['url' => '/admin/actualites/nouvelle', 'libelle' => 'Actualité', 'icone' => 'actualites'],
];
$compteLiens ??= [
    ['url' => '/admin/profil', 'libelle' => 'Mon profil', 'icone' => 'profil'],
    ['url' => '/admin/preferences', 'libelle' => 'Préférences', 'icone' => 'reglages'],
];

$icones = [
    'accueil' => 'M3 11 12 4l9 7M5 9.5V20h5v-6h4v6h5V9.5',
    'commandes' => 'M6 3h12v18l-3-2-3 2-3-2-3 2zM9 8h6M9 12h6M9 16h3',
    'demandes' => 'M3 13h5l2 3h4l2-3h5M5.5 5h13L21 13v6H3v-6z',
    'messages' => 'M4 5h16v11H9l-5 4z',
    'pages' => 'M6 3h8l4 4v14H6zM14 3v4h4M9 12h6M9 16h6',
    'actualites' => 'M4 5h13v14H6a2 2 0 0 1-2-2zM17 9h3v8a2 2 0 0 1-2 2M8 9h5M8 13h5M8 16h3',
    'medias' => 'M4 5h16v14H4zM4 16l5-5 4 4 2-2 5 5M15.5 9.5h.01',
    'membres' => 'M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM2.5 21a6.5 6.5 0 0 1 13 0M16 3.5a4 4 0 0 1 0 7.5M18 14.5a6.5 6.5 0 0 1 3.5 6.5',
    'evenements' => 'M4 6h16v15H4zM4 10h16M8 3v5M16 3v5',
    'reglages' => 'M4 7h10M18 7h2M4 17h4M12 17h8M16 5v4M10 15v4',
    'comptes' => 'M12 3 4 6v6c0 5 3.5 8 8 9 4.5-1 8-4 8-9V6zM9 12l2 2 4-4',
    'journal' => 'M9 6h11M9 12h11M9 18h11M4.5 6h.01M4.5 12h.01M4.5 18h.01',
    'site' => 'M14 4h6v6M20 4l-9 9M18 14v6H4V6h6',
    'replier' => 'M3 4h18v16H3zM9 4v16M16 10l-2 2 2 2',
    'menu' => 'M4 6h16M4 12h16M4 18h16',
    'fermer' => 'M6 6l12 12M18 6 6 18',
    'recherche' => 'M11 18a7 7 0 1 0 0-14 7 7 0 0 0 0 14zM20 20l-4-4',
    'plus' => 'M12 5v14M5 12h14',
    'cloche' => 'M6 16v-5a6 6 0 1 1 12 0v5l2 2H4zM10 21h4',
    'chevron' => 'm9 6 6 6-6 6',
    'profil' => 'M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM4 21a8 8 0 0 1 16 0',
    'deconnexion' => 'M15 4h4v16h-4M10 8l-4 4 4 4M6 12h10',
    'logo' => 'M12 3 20 7.5v9L12 21l-8-4.5v-9zM12 12l8-4.5M12 12v9M12 12 4 7.5',
    'lettre' => 'M21 3 3 10l7 3 3 7zM10 13l11-10',
    'liens' => 'M10 14a4 4 0 0 0 5.7 0l3-3a4 4 0 0 0-5.7-5.7l-1 1M14 10a4 4 0 0 0-5.7 0l-3 3a4 4 0 0 0 5.7 5.7l1-1',
    // Ajout du projet SSA (rubrique Lieux conventionnés) : repère de lieu.
    'lieu' => 'M12 21s-7-6.2-7-11.5A7 7 0 0 1 19 9.5C19 14.8 12 21 12 21zM12 12a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5z',
    'cle' => 'M8 19a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM11 12l9-9M16 7l3 3',
    'documents' => 'M7 3h7l5 5v13H7zM14 3v5h5M10 13h6M10 17h6',
    'sondage' => 'M5 20V10M12 20V4M19 20v-7',
    'dossier' => 'M3 6h6l2 2h10v11H3z',
    'succes' => 'M12 3a9 9 0 1 0 0 18 9 9 0 0 0 0-18zM8 12.5l2.75 2.75L16.5 9.5',
    'info' => 'M12 3a9 9 0 1 0 0 18 9 9 0 0 0 0-18zM12 11v5.5M12 7.5h.01',
    'attention' => 'M10.3 4.2 2.6 18a2 2 0 0 0 1.7 3h15.4a2 2 0 0 0 1.7-3L13.7 4.2a2 2 0 0 0-3.4 0ZM12 9.5v4.5M12 17.5h.01',
    'erreur' => 'M8.2 3h7.6L21 8.2v7.6L15.8 21H8.2L3 15.8V8.2ZM12 7.5v5.5M12 16.5h.01',
];
$ico = static fn (string $nom, string $classe = 'adm-ico'): string =>
    '<svg class="' . e($classe) . '" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="' . e($icones[$nom] ?? '') . '"/></svg>';

// Entrée courante : adresse exacte, ou section parente (aria-current="true" sur une fiche de la rubrique).
$courant = static function (string $url) use ($chemin, $accueil): ?string {
    if ($chemin === $url || $chemin === rtrim($url, '/')) {
        return 'page';
    }
    return $url !== $accueil && str_starts_with($chemin, rtrim($url, '/') . '/') ? 'true' : null;
};

// Menu filtré par rôle ; entrées des onglets du bas.
$groupes = [];
$bas = [];
foreach ((array) ($menu ?? require __DIR__ . '/../../config/admin-menu.php') as $groupe) {
    $entrees = array_values(array_filter(
        (array) ($groupe['entrees'] ?? []),
        static fn (array $x): bool => !isset($x['role']) || ($utilisateur['role'] ?? '') === $x['role']
    ));
    if ($entrees === []) {
        continue;
    }
    $groupes[] = ['titre' => (string) ($groupe['groupe'] ?? ''), 'entrees' => $entrees];
    foreach ($entrees as $x) {
        if (!empty($x['bas']) && count($bas) < 4) {
            $bas[] = $x;
        }
    }
}

$initiales = static function (string $nom): string {
    $mots = preg_split('/\s+/u', trim($nom)) ?: [];
    $i = '';
    foreach (array_slice($mots, 0, 2) as $m) {
        $i .= mb_strtoupper(mb_substr($m, 0, 1));
    }
    return $i;
};
$compteur = static function (int $n): string {
    return $n > 0
        ? '<span class="adm-compteur" aria-hidden="true">' . ($n > 99 ? '99+' : $n) . '</span><span class="adm-masque">, ' . $n . ' à traiter</span>'
        : '';
};
$lien = static function (array $x, string $classe = 'adm-nav__lien') use ($ico, $courant, $compteur): string {
    $c = $courant((string) $x['url']);
    return '<a class="' . $classe . '" href="' . e((string) $x['url']) . '"' . ($c ? ' aria-current="' . $c . '"' : '') . '>'
        . $ico((string) $x['icone']) . '<span class="adm-nav__texte">' . e((string) $x['libelle']) . '</span>'
        . $compteur((int) ($x['compteur'] ?? 0)) . '</a>';
};
$marque = static fn (string $classe = ''): string =>
    '<a class="adm-marque ' . $classe . '" href="' . e($accueil) . '"><span class="adm-marque__logo"><img src="/assets/img/logo-entete-112.webp" alt="" width="56" height="58"></span>'
    . '<span class="adm-marque__texte"><span class="adm-marque__nom">' . e($nomSite) . '</span><span class="adm-marque__sous">Administration</span></span></a>';
?><!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="robots" content="noindex, nofollow">
<title><?= e($titre) ?> · Administration <?= e($nomSite) ?></title>
<link rel="stylesheet" href="/assets/css/tokens.css">
<link rel="stylesheet" href="/assets/css/charte.css"><!-- charte du client (couleurs, polices), propre au projet -->
<link rel="stylesheet" href="/assets/css/composants.css"><!-- bouton, pastille-statut, etat-vide-chargement, champ-mot-de-passe, centre-notifications -->
<link rel="stylesheet" href="/assets/css/admin-coquille.css">
<link rel="stylesheet" href="/assets/css/minuteur-session.css">
<link rel="stylesheet" href="/assets/css/export.css">
<script src="/assets/js/minuteur-session.js" defer></script><!-- univers SSA : logo du client, propre au projet -->
<?php foreach ($styles as $href): ?><link rel="stylesheet" href="<?= e((string) $href) ?>">
<?php endforeach; ?>
<link rel="stylesheet" href="<?= e(asset('/assets/css/admin-ssa.css')) ?>"><!-- couche du projet, en dernier : elle prime sur la coquille et les modules -->
<script src="/assets/js/admin-coquille.js" defer></script>
<?php foreach ($scripts as $src): $estModule = is_array($src) && !empty($src['module']); $src = is_array($src) ? (string) $src['src'] : (string) $src; ?><script src="<?= e($src) ?>"<?= $estModule ? ' type="module"' : ' defer' ?>></script>
<?php endforeach; ?>
</head>
<body class="adm-corps">
<div class="adm adm-coquille<?= $variante === 'haut' ? ' adm-coquille--haut' : '' ?>" data-adm-coquille<?= $replie && $variante === 'laterale' ? ' data-replie' : '' ?>>
  <a class="adm-evitement" href="#contenu">Aller au contenu</a>
<?php if ($variante === 'laterale'): ?>
  <aside class="adm-barre" id="adm-barre" data-adm-barre aria-label="Navigation principale">
    <div class="adm-barre__tete">
      <?= $marque() ?>
      <button class="adm-icone-btn adm-barre__fermer" type="button" data-adm-fermer-tiroir><?= $ico('fermer') ?><span class="adm-masque">Fermer le menu</span></button>
    </div>
    <nav class="adm-nav" aria-label="Rubriques">
<?php foreach ($groupes as $k => $g): ?>
      <div class="adm-nav__groupe">
<?php if ($g['titre'] !== ''): ?>
        <p class="adm-nav__titre" id="adm-g<?= $k ?>"><?= e($g['titre']) ?></p>
<?php endif; ?>
        <ul<?= $g['titre'] !== '' ? ' aria-labelledby="adm-g' . $k . '"' : '' ?>>
<?php foreach ($g['entrees'] as $x): ?>
          <li><?= $lien($x) ?></li>
<?php endforeach; ?>
        </ul>
      </div>
<?php endforeach; ?>
    </nav>
    <div class="adm-barre__pied">
      <a class="adm-nav__lien" href="/" target="_blank" rel="noopener"><?= $ico('site') ?><span class="adm-nav__texte">Voir le site<span class="adm-masque"> (nouvel onglet)</span></span></a>
      <button class="adm-nav__lien adm-replier" type="button" aria-pressed="<?= $replie ? 'true' : 'false' ?>" data-adm-replier><?= $ico('replier') ?><span class="adm-nav__texte">Réduire le menu</span></button>
    </div>
  </aside>
  <div class="adm-voile" data-adm-voile></div>
<?php endif; ?>
  <div class="adm-colonne" data-adm-colonne>
    <header class="adm-haut">
      <?= $marque('adm-haut__marque') ?>
<?php if ($variante === 'haut'): ?>
      <nav class="adm-haut-nav" aria-label="Rubriques">
        <ul>
<?php foreach (array_merge(...array_column($groupes, 'entrees')) as $x): ?>
          <li><?= $lien($x) ?></li>
<?php endforeach; ?>
        </ul>
      </nav>
<?php elseif ($fil !== []): ?>
      <nav class="adm-fil" aria-label="Fil d'Ariane">
        <ol>
<?php foreach ($fil as $i => $f): ?>
<?php if ($i < count($fil) - 1 && isset($f['url'])): ?>
          <li><a href="<?= e((string) $f['url']) ?>"><?= e((string) $f['libelle']) ?></a><?= $ico('chevron', 'adm-ico adm-fil__sep') ?></li>
<?php else: ?>
          <li><span aria-current="page"><?= e((string) $f['libelle']) ?></span></li>
<?php endif; ?>
<?php endforeach; ?>
        </ol>
      </nav>
<?php endif; ?>
      <div class="adm-haut__outils">
<?php if ($recherche !== null): ?>
        <form class="adm-recherche" role="search" action="<?= e($recherche) ?>" method="get">
          <label class="adm-masque" for="adm-recherche">Rechercher</label>
          <?= $ico('recherche') ?>
          <input class="adm-saisie" id="adm-recherche" type="search" name="q" placeholder="Rechercher" autocomplete="off" data-adm-recherche aria-keyshortcuts="/" value="<?= e((string) ($_GET['q'] ?? '')) ?>">
          <kbd class="adm-touche" aria-hidden="true">/</kbd>
        </form>
        <a class="adm-icone-btn adm-recherche-lien" href="<?= e($recherche) ?>"><?= $ico('recherche') ?><span class="adm-masque">Rechercher</span></a>
<?php endif; ?>
<?php if ($creer !== []): ?>
        <details class="adm-menu adm-creer" data-adm-menu>
          <summary class="btn btn--petit"><?= $ico('plus') ?><span class="adm-masque adm-creer__texte">Créer</span></summary>
          <div class="adm-menu__panneau">
<?php foreach ($creer as $c): ?>
            <a class="adm-menu__item" href="<?= e($c['url']) ?>"><?= $ico($c['icone']) ?><?= e($c['libelle']) ?></a>
<?php endforeach; ?>
          </div>
        </details>
<?php endif; ?>
<?php if ($notifs !== false): ?>
        <?php partial('centre-notifications', ['notifs' => $notifs]) ?>
<?php endif; ?>
        <details class="adm-menu adm-compte" data-adm-menu>
          <summary><span class="adm-avatar" aria-hidden="true"><?= e($initiales((string) $utilisateur['nom'])) ?></span><span class="adm-masque">Compte de <?= e((string) $utilisateur['nom']) ?></span></summary>
          <div class="adm-menu__panneau">
            <p class="adm-menu__entete"><strong><?= e((string) $utilisateur['nom']) ?></strong><span class="adm-meta"><?= e((string) ($utilisateur['role_libelle'] ?? '')) ?></span></p>
            <hr class="adm-menu__separateur">
<?php foreach ($compteLiens as $c): ?>
            <a class="adm-menu__item" href="<?= e((string) $c['url']) ?>"><?= $ico((string) $c['icone']) ?><?= e((string) $c['libelle']) ?></a>
<?php endforeach; ?>
            <a class="adm-menu__item" href="/" target="_blank" rel="noopener"><?= $ico('site') ?>Voir le site<span class="adm-masque"> (nouvel onglet)</span></a>
            <hr class="adm-menu__separateur">
            <form method="post" action="<?= e($deconnexion) ?>">
              <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
              <button class="adm-menu__item" type="submit"><?= $ico('deconnexion') ?>Se déconnecter</button>
            </form>
          </div>
        </details>
      </div>
    </header>
    <main class="adm-contenu" id="contenu" tabindex="-1">
<?php if ($messages !== []): ?>
      <div class="adm-page adm-messages">
<?php foreach ($messages as $m):
    $ton = match ((string) ($m['type'] ?? 'succes')) { 'erreur', 'error', 'danger' => 'erreur', 'attention', 'warning' => 'attention', 'info' => 'info', default => 'succes' };
    $prefixe = ['erreur' => 'Erreur : ', 'attention' => 'Attention : ', 'info' => 'Information : ', 'succes' => 'Succès : '][$ton]; ?>
        <div class="adm-alerte<?= $ton !== 'info' ? ' adm-alerte--' . $ton : '' ?>" role="<?= $ton === 'erreur' ? 'alert' : 'status' ?>">
          <?= $ico($ton) ?>
          <p class="adm-alerte__texte"><span class="adm-masque"><?= $prefixe ?></span><?= e((string) ($m['texte'] ?? $m['message'] ?? '')) ?></p>
          <button class="adm-icone-btn adm-alerte__fermer" type="button" data-adm-alerte-fermer hidden><?= $ico('fermer') ?><span class="adm-masque">Fermer le message</span></button>
        </div>
<?php endforeach; ?>
      </div>
<?php endif; ?>
<?= $contenu ?>
    </main>
    <nav class="adm-onglets-bas" aria-label="Raccourcis">
      <ul>
<?php foreach ($bas as $x): ?>
        <li><a class="adm-onglet-bas" href="<?= e((string) $x['url']) ?>"<?= $courant((string) $x['url']) ? ' aria-current="' . $courant((string) $x['url']) . '"' : '' ?>><span class="adm-onglet-bas__icone"><?= $ico((string) $x['icone']) ?></span><span class="adm-onglet-bas__texte"><?= e((string) ($x['court'] ?? $x['libelle'])) ?></span><?= $compteur((int) ($x['compteur'] ?? 0)) ?></a></li>
<?php endforeach; ?>
<?php if ($variante === 'laterale'): ?>
        <li><button class="adm-onglet-bas" type="button" aria-expanded="false" aria-controls="adm-barre" data-adm-ouvrir-tiroir hidden><span class="adm-onglet-bas__icone"><?= $ico('menu') ?></span><span class="adm-onglet-bas__texte">Menu</span></button></li>
<?php endif; ?>
      </ul>
    </nav>
  </div>
</div>
<?= $apresContenu ?>
<?php partial('session/minuteur') // avertissement avant la fin de session (rien sans session connectée) ?>
</body>
</html>
