<?php
/**
 * Outil « Trouver un lieu » (décisions du 2026-11-17), commun à la page des lieux et à la page de la carte en grand.
 * Page des lieux : outils en haut, à gauche le texte de l'ancien article tant que rien n'est choisi puis les fiches, à
 * droite la carte (zoom, déplacement, légende posée dedans, lien vers la carte en grand). Carte en grand (comme la carte
 * en ligne du collectif, sous l'en-tête du site) : carte sur toute la hauteur, outils et légende posés dessus, fiches dans
 * une barre latérale à droite qui s'ouvre au choix d'une commune. Sans script : outils masqués, tout est affiché.
 * @var array $t @var list<array> $groupes @var array<string, int> $calques @var bool $pageCarte (carte en grand)
 */
use App\Site\Texte;

$pageCarte = !empty($pageCarte);
$icone = static fn (string $d, int $taille = 16): string => '<svg viewBox="0 0 24 24" width="' . $taille . '" height="' . $taille . '" aria-hidden="true" focusable="false"><path d="' . $d . '" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
$ligne = static function (array $l, string $cle) use ($t, $icone): void {
    // Adresse web d'une description (page de commande) : sortie du texte, en lien nommé.
    $description = trim((string) ($l['description'] ?? ''));
    $site = preg_match('#https?://\S+#', $description, $m) === 1 ? $m[0] : '';
    $description = trim((string) preg_replace('#\s*https?://\S+#', '', $description));
    $statut = (string) $l['statut'];
    ?>
          <li class="lieu" data-lieu data-calque="<?= e($statut) ?>">
            <div class="lieu__tete">
              <h4 class="lieu__nom"<?= langue_attr($l, 'nom') ?>><?= e((string) $l['producteur']) ?></h4>
              <p class="lieu__etat"><span class="pastille-calque pastille-calque--<?= e($statut) ?>" aria-hidden="true"></span><?= e($t['calque_court_' . $statut]) ?></p>
            </div>
            <p class="lieu__ou"><?= e($l['vente'] !== '' ? (string) $l['vente'] : $t['sur_place']) ?></p>
            <?php if ($description !== ''): ?><p class="lieu__description"<?= langue_attr($l, 'description') ?>><?= e(Texte::couper($description, 120)) ?></p><?php endif ?>
            <?php if (trim((string) $l['adresse'] . $l['ville']) !== ''): // adresse déduite de la position (IGN) ?><p class="lieu__adresse"><?= $icone('M12 21s-7-6.2-7-11.5A7 7 0 0 1 19 9.5C19 14.8 12 21 12 21zM12 12a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5z') ?><span><?= e(trim($l['adresse'] . ', ' . $l['code_postal'] . ' ' . $l['ville'], ' ,')) ?></span></p><?php endif ?>
            <p class="lieu__liens">
              <?php if ($l['latitude'] !== null && $l['longitude'] !== null): ?>
              <a class="lieu__bouton" href="https://www.openstreetmap.org/directions?to=<?= e($l['latitude'] . ',' . $l['longitude']) ?>" rel="noopener"><?= $icone('M12 21s-7-6.2-7-11.5A7 7 0 0 1 19 9.5C19 14.8 12 21 12 21zM12 12a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5z') ?><span><?= e($t['itineraire_court']) ?></span><span class="visually-hidden"> : <?= e((string) $l['nom']) ?></span></a>
              <button type="button" class="lieu__bouton lieu__bouton--secondaire" hidden data-zoom-lieu="<?= (int) $l['id'] ?>" data-groupe-du-lieu="<?= e($cle) ?>"><?= $icone('M9 4 3 6v14l6-2 6 2 6-2V4l-6 2-6-2zM9 4v14M15 6v14') ?><span><?= e($t['carte_court']) ?></span><span class="visually-hidden"> : <?= e((string) $l['nom']) ?></span></button>
              <?php endif ?>
              <?php if ($site !== ''): ?><a class="lieu__bouton lieu__bouton--secondaire" href="<?= e($site) ?>" rel="noopener"><?= $icone('M3 4h2l2.4 11h11l2-8H6.2M9 20a1 1 0 1 0 0-2 1 1 0 0 0 0 2zM17 20a1 1 0 1 0 0-2 1 1 0 0 0 0 2z') ?><span><?= e($t['commander']) ?></span><span class="visually-hidden"> : <?= e((string) $l['nom']) ?></span></a><?php endif ?>
            </p>
          </li>
<?php };
$parDefaut = ['ok' => true, 'attente' => true, 'refus' => false, 'sans' => false];
?>
<div class="lieux-explorer<?= $pageCarte ? ' lieux-explorer--page' : '' ?>" data-lieux data-un="<?= e($t['lieu_singulier']) ?>" data-plusieurs="<?= e($t['lieux_pluriel']) ?>" data-trouves="<?= e($t['resultat_recherche']) ?>">
  <form class="lieux-outils" hidden data-outils role="search" aria-label="<?= e($t['outils_titre']) ?>">
    <p class="lieux-outils__champ">
      <label for="commune-lieu"><?= e($t['commune_label']) ?></label>
      <select id="commune-lieu" data-commune-choix>
        <option value=""><?= e($t['toutes_communes']) ?></option>
        <?php foreach ($groupes as $g): ?><option value="<?= e($g['cle']) ?>"><?= e($g['commune']) ?></option><?php endforeach ?>
      </select>
    </p>
    <p class="lieux-outils__champ lieux-outils__champ--recherche">
      <label for="recherche-lieu"><?= e($t['recherche']) ?></label>
      <span class="lieux-liste__champ"><?= $icone('M11 18a7 7 0 1 0 0-14 7 7 0 0 0 0 14zM21 21l-5-5') ?><input type="search" id="recherche-lieu" autocomplete="off" placeholder="<?= e($t['recherche_exemple']) ?>" data-recherche-champ></span>
    </p>
    <p class="lieux-outils__actions"><button type="button" class="button button--ghost" data-tout><?= e($t['tout_afficher']) ?></button></p>
  </form>
  <div class="lieux-explorer__corps">
    <div class="lieux-panneau" data-panneau data-sans-apparition>
      <?php if ($pageCarte): ?>
      <p class="lieux-panneau__barre" hidden data-panneau-barre><a class="lien-fleche" href="<?= e(lurl('/lieux-conventionnes/')) ?>"><?= e($t['retour_page_lieux']) ?></a><button type="button" class="lieux-panneau__fermer" data-fermer-panneau><?= $icone('M6 6l12 12M18 6 6 18', 18) ?><span class="visually-hidden"><?= e($t['fermer_liste']) ?></span></button></p>
      <?php else: ?>
      <div class="lieux-intro" data-intro>
        <p><?= e($t['choix_texte']) ?></p>
        <p class="lieux-intro__date"><?= e($t['publie']) ?> <?= e($t['adresses_note']) ?></p>
        <p class="lieux-intro__indication" hidden data-synthese><?= e($t['indication']) ?></p>
        <div class="lieux-intro__calques">
          <h3><?= e($t['carte_calques_titre']) ?></h3>
          <p><?= e($t['carte_texte']) ?></p>
          <ul><?php foreach (['ok', 'attente', 'refus', 'sans'] as $c): ?><li><span class="pastille-calque pastille-calque--<?= e($c) ?>" aria-hidden="true"></span><?= e($t['calque_court_' . $c]) ?></li><?php endforeach ?></ul>
          <p class="lieux-intro__note"><?= e($t['carte_depart']) ?></p>
        </div>
      </div>
      <?php endif ?>
      <p class="visually-hidden" aria-live="polite" data-resume></p>
      <?php foreach ($groupes as $g): ?>
      <section class="lieux-groupe" id="<?= e($g['cle']) ?>" aria-labelledby="<?= e($g['cle']) ?>-titre" data-groupe>
        <h3 class="lieux-groupe__titre" id="<?= e($g['cle']) ?>-titre"><?= e($g['commune']) ?> <span class="lieux-liste__nombre" data-groupe-nombre></span></h3>
        <ul class="lieux"><?php foreach ($g['lieux'] as $l) $ligne($l, $g['cle']) ?></ul>
      </section>
      <?php endforeach ?>
      <p class="etat-vide" hidden data-aucun><?= e($t['aucun_resultat']) ?></p>
    </div>
    <figure class="lieux-carte">
      <div class="lieux-carte__dessin" tabindex="0" aria-label="<?= e($t['carte_clavier']) ?>" data-dessin>
        <?php partial('lieux/carte', ['groupes' => $groupes, 't' => $t, 'details' => $pageCarte]) ?>
        <div class="lieux-carte__controles" hidden data-controles>
          <button type="button" data-zoom="plus"><?= $icone('M12 5v14M5 12h14', 18) ?><span class="visually-hidden"><?= e($t['zoom_plus']) ?></span></button>
          <button type="button" data-zoom="moins"><?= $icone('M5 12h14', 18) ?><span class="visually-hidden"><?= e($t['zoom_moins']) ?></span></button>
          <button type="button" data-zoom="depart"><?= $icone('M3 11l9-7 9 7M5 10v10h5v-6h4v6h5V10', 18) ?><span class="visually-hidden"><?= e($t['zoom_depart']) ?></span></button>
        </div>
        <?php if (!$pageCarte): // raccourci de « Ouvrir la carte en grand », dans le coin de la carte ?>
        <a class="lieux-carte__plein" href="<?= e(lurl('/lieux-conventionnes/carte/')) ?>" target="_blank" rel="noopener" title="<?= e($t['carte_lien']) ?>"><?= $icone('M4 9V4h5M20 9V4h-5M4 15v5h5M20 15v5h-5', 20) ?><span class="visually-hidden"><?= e($t['carte_lien']) ?> (<?= e($t['nouvel_onglet']) ?>)</span></a>
        <?php endif ?>
        <fieldset class="lieux-calques" hidden data-calques>
          <legend><?= e($t['legende_titre']) ?></legend>
          <?php foreach ($calques as $c => $n): ?>
          <label class="lieux-calques__choix"><input type="checkbox" value="<?= e($c) ?>"<?= $parDefaut[$c] ? ' checked' : '' ?> data-calque-choix><span class="pastille-calque pastille-calque--<?= e($c) ?>" aria-hidden="true"></span><span><?= e($t['calque_court_' . $c]) ?> <span class="lieux-liste__nombre">(<?= $n ?>)</span></span></label>
          <?php endforeach ?>
        </fieldset>
      </div>
      <figcaption class="lieux-carte__pied">
        <span class="lieux-carte__credit"><?= e($t['carte_credit']) ?></span>
        <?php if (!$pageCarte): ?><a class="lien-fleche" href="<?= e(lurl('/lieux-conventionnes/carte/')) ?>" target="_blank" rel="noopener"><?= e($t['carte_lien']) ?><span class="visually-hidden"> (<?= e($t['nouvel_onglet']) ?>)</span><svg viewBox="0 0 16 16" width="16" height="16" aria-hidden="true" focusable="false"><path d="M3 8h10M9 4l4 4-4 4" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></a><?php endif ?>
      </figcaption>
    </figure>
  </div>
</div>
