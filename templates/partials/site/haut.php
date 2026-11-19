<?php
/**
 * Haut de page intérieure : fil d'Ariane visible et BreadcrumbList (rule web-seo), titre h1, chapeau.
 * @var string $chemin chemin interne @var string $titre @var string $chapo
 * Facultatif : array{chemin: string, libelle: string}|null $parent (rubrique d'une fiche) ;
 * string $photo (photo du site actuel à droite du titre, piste 2, chargée en priorité : élément LCP) ;
 * bool $etroit (en-tête dans la colonne de lecture de 40 rem, pages légales) ; bool $centre (en-tête centré, page isolée) ;
 * string $photoAlt (texte de remplacement de la photo quand elle porte une information : photo d'un article) ;
 * list<array{icone: string, texte?: string, html?: string}> $infos (infos courtes sous le chapô : date, heure, lieu d'un
 * article ; html : contenu déjà échappé, ex. une balise time) ;
 * string $classe (variante : page-head--article) ; string $cote (HTML déjà échappé, colonne de droite : lieu d'un événement) ; string $photoTaille (largeur affichée de la photo, sizes ; 40rem par défaut) ;
 * string $personnage (personnage de la boîte dans son rond jaune, à droite du titre) ; string $apres (HTML déjà échappé
 * sous les infos : actions de la page) ;
 * string $date (événement : date et heure, affichée en grand à gauche du titre) ; string $surTitre (au-dessus du titre daté) ;
 * array{src: string, largeur: int, hauteur: int} $illustration (illustration du site actuel à droite du titre, décorative, à la place d'une photo).
 */
use App\Langue\Langue;

$parent = isset($parent) && is_array($parent) ? $parent : null;
$niveaux = [['chemin' => '/', 'libelle' => t('menu.accueil')]];
if ($parent !== null) {
    $niveaux[] = $parent;
}
// Dernier niveau du fil d'Ariane : nom court de la page (celui du menu) quand le titre est long.
$niveaux[] = ['chemin' => $chemin, 'libelle' => !empty($filTitre) && is_string($filTitre) ? $filTitre : $titre];
$liste = [];
foreach ($niveaux as $i => $n) {
    $liste[] = ['@type' => 'ListItem', 'position' => $i + 1, 'name' => $n['libelle'], 'item' => Langue::absolue($n['chemin'])];
}
?>
<?php $photo = isset($photo) && is_string($photo) ? $photo : ''; $illustration = isset($illustration) && is_array($illustration) ? $illustration : []; $personnage = isset($personnage) && is_string($personnage) ? $personnage : ''; $cote = isset($cote) && is_string($cote) ? $cote : ''; ?>
<?php $etroit = !empty($etroit); // colonne de lecture (pages légales) : en-tête sur le même axe que le corps ?>
<div class="page-head<?= !empty($classe) && is_string($classe) ? ' ' . e($classe) : '' ?><?= $photo !== '' || $illustration !== [] || $personnage !== '' || $cote !== '' ? ' page-head--photo' : '' ?><?= $cote !== '' ? ' page-head--cote' : '' ?><?= $personnage !== '' ? ' page-head--personnage' : '' ?><?= $illustration !== [] ? ' page-head--illustration' : '' ?><?= $etroit ? ' page-head--etroit' : '' ?><?= !empty($centre) ? ' page-head--centre' : '' ?>">
  <div class="page-head__inner container">
    <div class="page-head__texte">
    <nav class="breadcrumb" aria-label="<?= e(t('site.fil_ariane')) ?>">
      <ol>
        <?php foreach ($niveaux as $i => $n): ?>
        <?php if ($i === count($niveaux) - 1): ?>
        <li aria-current="page"><?= e($n['libelle']) ?></li>
        <?php else: ?>
        <li><a href="<?= e(lurl($n['chemin'])) ?>"><?= e($n['libelle']) ?></a></li>
        <?php endif ?>
        <?php endforeach ?>
      </ol>
    </nav>
    <?php if (!empty($date) && is_string($date)): $pd = \App\Site\Dates::pastille($date); // événement : date en grand à gauche du titre ?>
    <div class="page-head__date-titre">
      <time class="page-head__date" datetime="<?= e(\App\Site\Dates::iso($date)) ?>"><strong><?= e($pd['jour']) ?></strong><span><?= e($pd['mois']) ?> <?= e(substr($date, 0, 4)) ?></span></time>
      <div>
        <?php if (!empty($surTitre) && is_string($surTitre)): ?><p class="sur-titre"><?= e($surTitre) ?></p><?php endif ?>
        <h1><?= e(typo($titre)) ?></h1>
      </div>
    </div>
    <?php else: ?>
    <h1><?= e(typo($titre)) ?></h1>
    <?php endif ?>
    <?php if (($chapo ?? '') !== ''): ?><p class="page-head__intro"><?= e(typo($chapo)) ?></p><?php endif ?>
    <?php if (!empty($infos) && is_array($infos)): ?>
    <ul class="page-head__infos">
      <?php foreach ($infos as $info): ?><li><svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" focusable="false"><path d="<?= e((string) $info['icone']) ?>" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg><?= $info['html'] ?? e((string) $info['texte']) ?></li><?php endforeach ?>
    </ul>
    <?php endif ?>
    <?php if (!empty($apres) && is_string($apres)): ?><?= $apres // HTML déjà échappé par la page appelante ?><?php endif ?>
    </div>
    <?php if ($photo !== ''): [$pl, $ph] = \App\Site\Vignette::taille($photo) ?? [1600, 1200]; // proportion réelle : jamais recadrée ni agrandie au-delà de sa taille ?><img class="page-head__photo" <?= \App\Site\Vignette::attributs($photo, '(max-width: 52rem) 100vw, ' . (!empty($photoTaille) && is_string($photoTaille) ? $photoTaille : '40rem'), 960) ?> alt="<?= e(isset($photoAlt) && is_string($photoAlt) ? $photoAlt : '') ?>" width="<?= $pl ?>" height="<?= $ph ?>" fetchpriority="high" decoding="async"><?php endif ?>
    <?php if ($cote !== ''): ?><div class="page-head__cote"><?= $cote // HTML déjà échappé par la page appelante ?></div><?php endif ?>
    <?php if ($personnage !== ''): ?><span class="page-head__personnage" aria-hidden="true"><span class="portail portail--tete portail--buste<?= !empty($miroir) ? ' portail--miroir' : '' ?>"><span class="portail__calque"><img src="<?= e($personnage) ?>" alt="" width="212" height="313" decoding="async"></span></span></span><?php endif ?>
    <?php if ($illustration !== []): ?><img class="page-head__illustration" src="<?= e((string) $illustration['src']) ?>" alt="" width="<?= (int) $illustration['largeur'] ?>" height="<?= (int) $illustration['hauteur'] ?>" decoding="async"><?php endif ?>
  </div>
</div>
<script type="application/ld+json"><?= json_encode(['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => $liste], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) ?></script>
