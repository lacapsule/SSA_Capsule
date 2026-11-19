<?php
/**
 * Gabarit des écrans de connexion du back-office, au langage de sections/admin-connexion (.cnx-*) et des primitives
 * .adm-* de gabarits/admin-coquille : marque, carte centrée, pied discret. Aucune donnée de l'application.
 * Feuilles : tokens, composants (bouton, champ-mot-de-passe), admin-coquille.css, admin-connexion.css, puis
 * admin-auth.css (style.css de ce module : seulement le code à 6 chiffres, le QR code, la clé et les codes de secours).
 * @var string $content @var string $title @var list<array{message: string, type: string}> $flashes @var bool|null $qr @var bool|null $wide
 */
$site = (string) config('app.name', 'Back-office');
?>
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?= e($title) ?> · <?= e($site) ?></title>
<?php foreach (['/assets/css/tokens.css', '/assets/css/charte.css', '/assets/css/composants.css', '/assets/css/admin-coquille.css', '/assets/css/admin-connexion.css', '/assets/css/admin-auth.css', '/assets/css/admin-ssa.css'] as $href): ?>
<link rel="stylesheet" href="<?= e(asset($href)) ?>">
<?php endforeach ?>
<?php if (!empty($qr)): ?><script src="<?= e(asset('/assets/js/qrcode.min.js')) ?>" defer></script><?php endif ?>
<script src="<?= e(asset('/assets/js/admin-auth.js')) ?>" defer></script>
<script src="<?= e(asset('/assets/js/mot-de-passe.js')) ?>" defer></script>
</head>
<body class="adm-corps">
<div class="adm cnx">
  <div class="cnx__colonne">
    <header class="cnx__entete">
      <div class="cnx-marque"><span class="cnx-marque__logo"><img src="/assets/img/logo-entete-112.webp" alt="" width="56" height="58"></span><span class="cnx-marque__texte"><span class="cnx-marque__nom"><?= e($site) ?></span><span class="cnx-marque__sous">Administration</span></span></div>
    </header>
    <main class="cnx__principal" id="contenu" tabindex="-1">
      <div class="adm-carte cnx-carte<?= !empty($wide) ? ' auth-carte--large' : '' ?>">
        <?php foreach ($flashes as $f): $ton = match ($f['type']) { 'error', 'erreur' => 'erreur', 'warning', 'attention' => 'attention', 'info' => 'info', default => 'succes' }; ?>
          <p class="adm-alerte<?= $ton !== 'info' ? ' adm-alerte--' . $ton : '' ?>" role="<?= $ton === 'erreur' ? 'alert' : 'status' ?>"><?= e($f['message']) ?></p>
        <?php endforeach ?>
        <?= $content ?>
      </div>
    </main>
    <footer class="cnx__pied">
      <a href="/"><svg class="adm-ico" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M19 12H5M11 18l-6-6 6-6"/></svg>Retour au site</a>
      <p class="cnx__securite"><svg class="adm-ico" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/></svg>Connexion chiffrée, essais limités</p>
    </footer>
  </div>
</div>
</body>
</html>
