<?php
/**
 * Plan du site (maquette docs/maquettes/plan-du-site.md), page isolée centrée (rule web-design, « Centrage ») : pages du
 * menu et informations légales à côté des actualités, puis tous les événements par année, ceux de même titre
 * regroupés (une date par lien) ; empilé au téléphone. Toutes les rubriques visibles au premier écran ; tout vient des mêmes sources que le menu et les listes.
 * @var array<string, string> $t @var string $path @var list<array> $articles @var list<array> $evenements
 */
use App\Site\Dates;
use App\Site\Site;

partial('site/haut', ['chemin' => $path, 'titre' => $t['titre'], 'chapo' => $t['intro'], 'centre' => true]);
$annees = [];
foreach ($evenements as $ev) {
    $annees[substr((string) $ev['starts_at'], 0, 4)][] = $ev;
}
krsort($annees);
foreach ($annees as &$liste) {
    usort($liste, static fn (array $a, array $b): int => strcmp((string) $b['starts_at'], (string) $a['starts_at']));
}
unset($liste);
$court = static function (string $date): string {
    $p = Dates::pastille($date);
    return $p['jour'] . ' ' . $p['mois'];
};
?>
<div class="section">
  <div class="plan container">
    <div class="plan__colonne">
      <section aria-labelledby="plan-pages">
        <h2 id="plan-pages"><?= e($t['pages_titre']) ?></h2>
        <ul class="plan__liste plan__liste--pages">
          <?php foreach ((array) Site::get('menu', []) as $href => $cle): ?><li><a href="<?= e(lurl((string) $href)) ?>"><?= e(t((string) $cle)) ?></a></li><?php endforeach ?>
        </ul>
      </section>
      <section aria-labelledby="plan-legal">
        <h2 id="plan-legal"><?= e($t['legal_titre']) ?></h2>
        <ul class="plan__liste plan__liste--pages"><?php foreach ((array) Site::get('legal', []) as $href => $cle): if ($href === $path) continue; ?><li><a href="<?= e(lurl((string) $href)) ?>"><?= e(t((string) $cle)) ?></a></li><?php endforeach ?></ul>
      </section>
    </div>
    <?php if ($articles !== []): ?>
    <section class="plan__colonne" aria-labelledby="plan-actualites">
      <h2 id="plan-actualites"><a href="<?= e(lurl('/actualites/')) ?>"><?= e($t['actualites_titre']) ?></a> <span class="plan__nombre">(<?= count($articles) ?>)</span></h2>
      <ul class="plan__liste plan__liste--dates">
        <?php foreach ($articles as $a): ?><li><time datetime="<?= e(Dates::iso((string) $a['date_article'])) ?>"><?= e($court((string) $a['date_article'])) ?> <?= e(substr((string) $a['date_article'], 0, 4)) ?></time><a href="<?= e(lurl('/actualites/' . $a['slug'] . '/')) ?>"<?= langue_attr($a, 'titre') ?>><?= e((string) $a['titre']) ?></a></li><?php endforeach ?>
      </ul>
    </section>
    <?php endif ?>
    <?php if ($annees !== []): ?>
    <section class="plan__colonne plan__colonne--evenements" aria-labelledby="plan-agenda">
      <h2 id="plan-agenda"><a href="<?= e(lurl('/agenda/')) ?>"><?= e($t['agenda_titre']) ?></a> <span class="plan__nombre">(<?= count($evenements) ?>)</span></h2>
      <?php foreach ($annees as $annee => $liste): $parTitre = [];
          foreach ($liste as $ev) { $parTitre[(string) $ev['title']][] = $ev; } ?>
      <h3 class="plan__annee"><?= e((string) $annee) ?></h3>
      <ul class="plan__liste plan__liste--evenements">
        <?php foreach ($parTitre as $titre => $memes): ?>
        <li>
          <?php if (count($memes) === 1): $ev = $memes[0]; ?>
          <time datetime="<?= e(Dates::iso((string) $ev['starts_at'])) ?>"><?= e($court((string) $ev['starts_at'])) ?></time><a href="<?= e(lurl('/agenda/' . $ev['slug'] . '/')) ?>"<?= langue_attr($ev, 'title') ?>><?= e($titre) ?></a>
          <?php else: ?>
          <span class="plan__nombre"><?= count($memes) ?>&times;</span><span><span class="plan__titre-groupe"<?= langue_attr($memes[0], 'title') ?>><?= e($titre) ?></span>
            <span class="plan__dates"><?php foreach ($memes as $i => $ev): ?><?= $i > 0 ? ', ' : '' ?><a href="<?= e(lurl('/agenda/' . $ev['slug'] . '/')) ?>"><time datetime="<?= e(Dates::iso((string) $ev['starts_at'])) ?>"><?= e($court((string) $ev['starts_at'])) ?></time><span class="visually-hidden"> : <?= e($titre) ?></span></a><?php endforeach ?></span></span>
          <?php endif ?>
        </li>
        <?php endforeach ?>
      </ul>
      <?php endforeach ?>
    </section>
    <?php endif ?>
  </div>
</div>
