<?php
/**
 * Les structures porteuses en cartes à logo, lues dans la table des partenaires (groupe « partenaire », ordre réglé au
 * tableau de bord) ; chaque carte mène au site de sa structure quand il existe (rule web-design, « Logos de
 * partenaires et financeurs »). Nom et rôle traduits comme les autres contenus (objet « partenaire »).
 * Facultatif : string $variante ('' ou 'large' : quatre colonnes sur grand écran).
 */
use App\Core\Db;
use App\Langue\Contenu;

$structures = Db::run("SELECT id, nom, url, logo, description FROM partenaires WHERE groupe = 'partenaire' ORDER BY position, nom")->fetchAll();
if ($structures === []) {
    return;
}
$variante = isset($variante) && $variante === 'large' ? ' structures--large' : '';
?>
<ul class="structures<?= $variante ?>">
  <?php foreach ($structures as $s): $t = Contenu::traduire('partenaire', (string) $s['id'], ['nom' => (string) $s['nom'], 'description' => (string) ($s['description'] ?? '')]); $logo = (string) $s['logo']; $site = preg_match('#^https?://#i', (string) $s['url']) === 1 ? (string) $s['url'] : ''; ?>
  <li class="structure<?= $site !== '' ? ' structure--lien' : '' ?>">
    <?php if ($logo !== '' && is_file(ROOT . '/public' . $logo)): [$lw, $lh] = getimagesize(ROOT . '/public' . $logo) ?: [160, 64]; // Logo presque carré : plus haut, pour peser autant qu'un logo en longueur. ?><img src="<?= e($logo) ?>" alt="" width="<?= (int) $lw ?>" height="<?= (int) $lh ?>"<?= $lw / max(1, $lh) < 1.5 ? ' class="structure__logo--carre"' : '' ?> loading="lazy" decoding="async"><?php endif ?>
    <p><strong<?= langue_attr($t, 'nom') ?>><?php if ($site !== ''): ?><a href="<?= e($site) ?>" rel="noopener"><?= e($t['nom']) ?></a><?php else: ?><?= e($t['nom']) ?><?php endif ?></strong><?php if ($t['description'] !== ''): ?><span<?= langue_attr($t, 'description') ?>><?= e($t['description']) ?></span><?php endif ?></p>
  </li>
  <?php endforeach ?>
</ul>
