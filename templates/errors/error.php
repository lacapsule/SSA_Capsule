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
<link rel="stylesheet" href="<?= e(asset('/assets/css/site.css')) ?>">
</head>
<body>
<main id="contenu" class="wrap" tabindex="-1">
  <h1><?= e($title) ?></h1>
  <p><?= e($message ?? ($texts[$status] ?? 'Une erreur est survenue.')) ?></p>
  <p><a href="/">Retour à l'accueil</a></p>
</main>
</body>
</html>
