<?php
/**
 * État d'une zone de contenu admin : vide, sans résultat, erreur ou succès (composants/etat-vide-chargement).
 * Usage : $etat = [...]; require __DIR__ . '/etat.php';
 * Suppose la fonction d'échappement du socle : function e(string $v): string (htmlspecialchars, ENT_QUOTES, UTF-8).
 * Dans un tableau : <tr><td class="adm-etat-cellule" colspan="N"><?php require 'etat.php' ?></td></tr>
 */

/** @var array{type?: string, taille?: string, titre: string, texte?: string, titre_niveau?: int, detail?: string, actions?: list<array{libelle: string, href?: string, principal?: bool, icone?: string}>} $etat */
$etat ??= [
    'type' => 'vide',            // vide | recherche | erreur | succes
    'taille' => 'page',          // page | compact
    'titre' => "Aucune commande pour l'instant",
    'texte' => "Les commandes passées sur la boutique arrivent ici. Vous pouvez aussi en saisir une reçue par téléphone ou au comptoir.",
    'titre_niveau' => 2,         // h2 en page, 0 pour un simple paragraphe (cellule de tableau, carte déjà titrée)
    'detail' => '',              // erreur : détail technique replié (code, référence)
    'actions' => [
        ['libelle' => 'Nouvelle commande', 'href' => '/admin/commandes/nouvelle', 'principal' => true, 'icone' => 'plus'],
        ['libelle' => 'Voir la boutique', 'href' => '/boutique/'],
    ],
];

$type = in_array($etat['type'] ?? 'vide', ['vide', 'recherche', 'erreur', 'succes'], true) ? ($etat['type'] ?? 'vide') : 'vide';
$compact = ($etat['taille'] ?? 'page') === 'compact';
$niveau = (int) ($etat['titre_niveau'] ?? ($compact ? 0 : 2));
$balise = $niveau >= 2 && $niveau <= 6 ? 'h' . $niveau : 'p';

$classes = 'adm-etat' . ($compact ? ' adm-etat--compact' : '') . ($type === 'erreur' ? ' adm-etat--erreur' : '') . ($type === 'succes' ? ' adm-etat--succes' : '');
// Erreur : annoncée tout de suite ; succès : annoncé poliment ; vide : contenu ordinaire.
$role = ['erreur' => ' role="alert"', 'succes' => ' role="status"'][$type] ?? '';

$illustrations = [
    'vide' => '<path d="M10 36 18 18h28l8 18v13a3 3 0 0 1-3 3H13a3 3 0 0 1-3-3z"/><path d="M10 36h13l3 5h12l3-5h13"/><path class="adm-etat__trait-teinte" d="M32 6v6M21 9l3 4.5M43 9l-3 4.5"/>',
    'recherche' => '<circle cx="28" cy="28" r="15"/><path d="m39 39 14 14"/><path class="adm-etat__trait-teinte" d="M22 28h12"/>',
    'erreur' => '<path d="M28.5 11.5a4 4 0 0 1 7 0l20 35A4 4 0 0 1 52 52.5H12a4 4 0 0 1-3.5-6z"/><path class="adm-etat__trait-teinte" d="M32 25v12M32 44.5v.5"/>',
    'succes' => '<circle class="adm-etat__fond-teinte" cx="32" cy="32" r="21"/><circle cx="32" cy="32" r="21"/><path class="adm-etat__trait-teinte" d="m22.5 32.5 6.5 6.5 12.5-13.5"/>',
];
$icones = [
    'plus' => '<path d="M12 5v14M5 12h14"/>',
    'reessayer' => '<path d="M20 11a8 8 0 1 0-2.3 5.7M20 5v6h-6"/>',
];
$petit = $compact ? ' btn--petit' : '';
?>
<div class="<?= $classes ?>"<?= $role ?>>
  <div class="adm-etat__illustration">
    <svg viewBox="0 0 64 64" aria-hidden="true" focusable="false"><?= $illustrations[$type] ?></svg>
  </div>
  <<?= $balise ?> class="adm-etat__titre"><?= e($etat['titre']) ?></<?= $balise ?>>
  <?php if (($etat['texte'] ?? '') !== ''): ?>
  <p class="adm-etat__texte"><?= e($etat['texte']) ?></p>
  <?php endif ?>
  <?php if ($type === 'erreur' && ($etat['detail'] ?? '') !== ''): ?>
  <details class="adm-etat__detail">
    <summary><svg class="adm-ico" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="m9 6 6 6-6 6"/></svg>Détail technique</summary>
    <code><?= e($etat['detail']) ?></code>
  </details>
  <?php endif ?>
  <?php if (!empty($etat['actions'])): ?>
  <div class="adm-etat__actions">
    <?php foreach ($etat['actions'] as $a):
        $cls = 'btn' . $petit . (empty($a['principal']) ? ' btn--neutre' : '');
        $ico = isset($a['icone'], $icones[$a['icone']]) ? '<svg class="adm-ico" viewBox="0 0 24 24" aria-hidden="true" focusable="false">' . $icones[$a['icone']] . '</svg>' : '';
    ?>
      <?php if (isset($a['href'])): ?>
    <a class="<?= $cls ?>" href="<?= e($a['href']) ?>"><?= $ico ?><?= e($a['libelle']) ?></a>
      <?php else: /* Bouton sans lien : « Réessayer » relance la requête (data-adm-reessayer, à brancher), ou un formulaire POST autour. */ ?>
    <button class="<?= $cls ?>" type="button" data-adm-reessayer><?= $ico ?><?= e($a['libelle']) ?></button>
      <?php endif ?>
    <?php endforeach ?>
  </div>
  <?php endif ?>
</div>
