<?php
/**
 * Balises de langue du <head> (modules/multilingue) : canonical de la version affichée, hreflang réciproques et
 * x-default, og:locale. Remplace la ligne canonical de la mise en page :
 *   <?php partial('langue/head', ['disponibles' => $disponibles ?? null, 'noindex' => $noindex ?? false]) ?>
 * @var list<string>|null $disponibles langues où la page existe (null : toutes) @var bool $noindex
 * Facultatif : string $chemin (chemin interne ; par défaut celui de la requête).
 * Page demandée dans une langue où elle n'est pas traduite : noindex, pas de canonical (le texte affiché est une
 * copie de la version d'origine, il ne doit pas entrer dans l'index sous cette adresse).
 */
use App\Langue\Langue;

$chemin = isset($chemin) && is_string($chemin) ? $chemin : Langue::chemin();
$disponibles = isset($disponibles) && is_array($disponibles) ? $disponibles : null;
$traduite = in_array(Langue::actuelle(), Langue::filtrer($disponibles), true);
$noindex = !empty($noindex) || !$traduite;
?>
<?php if ($noindex): ?>
<meta name="robots" content="noindex">
<?php else: ?>
<link rel="canonical" href="<?= e(Langue::absolue($chemin)) ?>">
<?php foreach (Langue::alternatives($chemin, $disponibles) as $alt): ?>
<link rel="alternate" hreflang="<?= e($alt['hreflang']) ?>" href="<?= e($alt['href']) ?>">
<?php endforeach ?>
<?php endif ?>
<meta property="og:locale" content="<?= e(Langue::locale()) ?>">
<?php foreach (Langue::filtrer($disponibles) as $code): if ($code !== Langue::actuelle()): ?>
<meta property="og:locale:alternate" content="<?= e(Langue::locale($code)) ?>">
<?php endif; endforeach ?>
