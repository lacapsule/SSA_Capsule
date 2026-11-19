<?php
/** Page d'erreur autonome (aucune requête en base, aucun partiel) : elle doit s'afficher même quand le reste échoue. @var int $status @var string|null $message */
$titles = [400 => 'Demande incomplète', 403 => 'Accès refusé', 404 => 'Page introuvable', 405 => 'Méthode non autorisée', 429 => 'Trop de demandes', 500 => 'Erreur du serveur', 503 => 'Service indisponible'];
$texts = [
    400 => 'La demande n\'a pas pu être traitée. Rechargez la page et recommencez.',
    403 => 'Vous n\'avez pas accès à cette page.',
    404 => 'Cette adresse ne correspond à aucune page. Elle a peut-être changé.',
    405 => 'Cette adresse ne s\'utilise pas de cette façon.',
    429 => 'Beaucoup de demandes en peu de temps. Patientez un instant puis réessayez.',
    500 => 'Un problème est survenu de notre côté. Réessayez dans un instant.',
    503 => 'Le site est en maintenance. Réessayez dans quelques minutes.',
];
$title = $titles[$status] ?? 'Erreur';
?>
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
<title><?= e($title) ?> | <?= e((string) config('app.name', 'Site')) ?></title>
<?php foreach (['tokens', 'charte', 'site', 'ssa'] as $feuille): // styles du site (fichiers statiques), sans requête ni partiel ?>
<link rel="stylesheet" href="<?= e(asset('/assets/css/' . $feuille . '.css')) ?>">
<?php endforeach ?>
</head>
<body>
<header class="site-header">
  <div class="site-header__inner container">
    <a class="site-header__logo" href="/"><img src="/assets/img/logo-entete-112.webp" alt="" width="56" height="58"><span><?= e((string) config('app.name', 'Site')) ?></span></a>
  </div>
</header>
<main id="contenu" tabindex="-1">
  <section class="section introuvable" aria-labelledby="erreur-titre">
    <div class="container introuvable__inner">
      <p class="introuvable__code" aria-hidden="true"><?= (int) $status ?></p>
      <p class="sur-titre">Erreur <?= (int) $status ?></p>
      <h1 id="erreur-titre"><?= e($title) ?></h1>
      <p class="introuvable__texte"><?= e($message ?? ($texts[$status] ?? 'Une erreur est survenue.')) ?></p>
      <p class="introuvable__actions"><a class="button" href="/">Retour à l'accueil</a></p>
    </div>
  </section>
</main>
</body>
</html>
