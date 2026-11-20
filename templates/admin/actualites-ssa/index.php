<?php
/**
 * Liste des articles (App\Actualites\ActualitesAdmin), du plus récent au plus ancien ; recherche instantanée (admin.js).
 * @var list<array<string, mixed>> $articles @var string $aujourdhui
 */
use App\Admin\Admin;
use App\Admin\Ui;
use App\Site\Dates;

$base = Admin::url('actualites/');
?>
<header class="adm-entete-page">
  <div class="adm-entete-page__textes">
    <h1 class="adm-entete-page__titre">Actualités</h1>
    <p class="adm-entete-page__sous-titre">Les articles de la page Actualités ; les trois plus récents sont repris sur l'accueil.</p>
  </div>
  <div class="adm-entete-page__actions"><a class="btn" href="<?= e($base . 'nouveau/') ?>"><?= Ui::icon('plus') ?>Écrire un article</a></div>
</header>
<div class="adm-carte adm-liste" data-list>
  <?php if (count($articles) > 5) { $filters = []; $searchLabel = 'Rechercher un article'; $countWords = ['article', 'articles']; $total = count($articles); require ROOT . '/templates/admin/partials/filters.php'; } ?>
  <section data-filter-grid aria-label="Articles">
    <?php if ($articles === []): ?>
    <p class="adm-carte__vide">Aucun article pour l'instant. <a href="<?= e($base . 'nouveau/') ?>">Écrire un article</a></p>
    <?php else: ?>
    <ul class="adm-lignes" role="list">
      <?php foreach ($articles as $a): $href = $base . (int) $a['id'] . '/'; $video = json_decode((string) ($a['videos'] ?? ''), true)[0] ?? null; $vignette = $a['image'] ?: ($video['poster'] ?? ''); ?>
      <li data-filter-row>
        <span class="adm-lignes__vignette"><?php if ($vignette): ?><img src="<?= e((string) $vignette) ?>" alt="" loading="lazy" decoding="async"><?php else: ?><?= Ui::icon('image') ?><?php endif ?></span>
        <a class="adm-lignes__principal" href="<?= e($href) ?>">
          <span class="adm-lignes__titre"><?= e((string) $a['titre']) ?></span>
          <span class="adm-meta"><?= e(Dates::longue((string) $a['date_article'])) ?><?= $video ? ' · vidéo' : '' ?></span>
        </a>
        <?php if ((int) $a['is_published'] !== 1): ?><span class="pastille pastille--point pastille--neutre">Masqué</span>
        <?php elseif ((string) $a['date_article'] > $aujourdhui): ?><span class="pastille pastille--point pastille--info">Programmé</span><?php endif ?>
        <a class="btn btn--neutre" href="<?= e($href) ?>"><?= Ui::icon('edit') ?>Modifier<span class="adm-masque"> <?= e((string) $a['titre']) ?></span></a>
      </li>
      <?php endforeach ?>
    </ul>
    <?php endif ?>
  </section>
  <div class="adm-etat adm-etat--compact" data-filter-empty hidden><p class="adm-etat__titre">Aucun résultat</p><p class="adm-etat__texte">Aucun article ne correspond à la recherche.</p><button class="btn btn--neutre" type="button" data-filter-reset>Effacer la recherche</button></div>
</div>
