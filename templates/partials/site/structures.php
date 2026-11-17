<?php
/**
 * Les quatre structures porteuses en cartes à logo ; chaque carte mène au site de sa structure quand il existe
 * (rule web-design, « Logos de partenaires et financeurs »). Noms et rôles : textes de la page Qui sommes-nous ;
 * logos : config/site.php (« accueil.logos_structures ») ; adresses : table des partenaires (administration).
 * @var array<string, string> $textes textes de /projet/ (structure_n_nom, structure_n_texte)
 * Facultatif : string $variante ('' ou 'large' : quatre colonnes sur grand écran).
 */
use App\Core\Db;
use App\Site\Site;

$logos = (array) (((array) Site::get('accueil', []))['logos_structures'] ?? []);
$sites = [];
foreach (Db::run('SELECT logo, url FROM partenaires')->fetchAll() as $l) {
    if (preg_match('#^https?://#i', (string) $l['url']) === 1) {
        $sites[(string) $l['logo']] = (string) $l['url'];
    }
}
$variante = isset($variante) && $variante === 'large' ? ' structures--large' : '';
?>
<ul class="structures<?= $variante ?>">
  <?php foreach ([1, 2, 3, 4] as $n): $logo = (string) ($logos[$n - 1] ?? ''); $site = $sites[$logo] ?? ''; $nom = (string) $textes['structure_' . $n . '_nom']; ?>
  <li class="structure<?= $site !== '' ? ' structure--lien' : '' ?>">
    <?php if ($logo !== '' && is_file(ROOT . '/public' . $logo)): [$lw, $lh] = getimagesize(ROOT . '/public' . $logo) ?: [160, 64]; // Logo presque carré : plus haut, pour peser autant qu'un logo en longueur. ?><img src="<?= e($logo) ?>" alt="" width="<?= (int) $lw ?>" height="<?= (int) $lh ?>"<?= $lw / max(1, $lh) < 1.5 ? ' class="structure__logo--carre"' : '' ?> loading="lazy" decoding="async"><?php endif ?>
    <p><strong><?php if ($site !== ''): ?><a href="<?= e($site) ?>" rel="noopener"><?= e($nom) ?></a><?php else: ?><?= e($nom) ?><?php endif ?></strong><?= e((string) $textes['structure_' . $n . '_texte']) ?></p>
  </li>
  <?php endforeach ?>
</ul>
