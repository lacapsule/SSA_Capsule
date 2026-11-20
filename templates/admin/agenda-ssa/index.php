<?php
/**
 * Liste des événements (App\Agenda\AgendaAdmin) : onglets À venir et Passés (liens serveur), recherche instantanée
 * (admin.js, data-list), une ligne par événement : date, titre, lieu, état.
 * @var list<array<string, mixed>> $evenements @var bool $passes @var array{avenir: int, passes: int} $nombres
 */
use App\Admin\Admin;
use App\Admin\Ui;
use App\Site\Dates;

$base = Admin::url('agenda/');
?>
<header class="adm-entete-page">
  <div class="adm-entete-page__textes">
    <h1 class="adm-entete-page__titre">Agenda</h1>
    <p class="adm-entete-page__sous-titre">Les rendez-vous de la page Agenda et de l'accueil. Un événement passé reste visible dans les « Événements passés ».</p>
  </div>
  <div class="adm-entete-page__actions">
    <?php $adresse = Admin::url('agenda/export'); $quoi = 'tout l\'agenda'; require ROOT . '/templates/admin/partials/exporter.php'; ?>
    <a class="btn" href="<?= e($base . 'nouveau/') ?>"><?= Ui::icon('plus') ?>Ajouter un événement</a>
  </div>
</header>
<nav class="adm-onglets" aria-label="Période">
  <ul>
    <li><a href="<?= e($base) ?>"<?= !$passes ? ' aria-current="page"' : '' ?>>À venir <span class="adm-compteur adm-compteur--neutre"><?= $nombres['avenir'] ?></span></a></li>
    <li><a href="<?= e($base . '?passes') ?>"<?= $passes ? ' aria-current="page"' : '' ?>>Passés <span class="adm-compteur adm-compteur--neutre"><?= $nombres['passes'] ?></span></a></li>
  </ul>
</nav>
<div data-list>
  <?php if (count($evenements) > 5) { $filters = []; $searchLabel = 'Rechercher un événement'; $countWords = ['événement', 'événements']; $total = count($evenements); require ROOT . '/templates/admin/partials/filters.php'; } ?>
  <section class="adm-carte" data-filter-grid aria-label="<?= $passes ? 'Événements passés' : 'Événements à venir' ?>">
    <?php if ($evenements === []): ?>
    <p class="adm-carte__vide"><?= $passes ? 'Aucun événement passé.' : 'Aucun événement à venir.' ?><?php if (!$passes): ?> <a href="<?= e($base . 'nouveau/') ?>">Ajouter un événement</a><?php endif ?></p>
    <?php else: ?>
    <ul class="adm-lignes" role="list">
      <?php foreach ($evenements as $ev): $href = $base . (int) $ev['id'] . '/'; ?>
      <li data-filter-row>
        <a class="adm-lignes__principal" href="<?= e($href) ?>">
          <span class="adm-lignes__titre"><?= e((string) $ev['title']) ?></span>
          <span class="adm-meta"><?= e(Dates::longue((string) $ev['starts_at']) . ', ' . Dates::heure((string) $ev['starts_at'])) ?><?= $ev['address'] ? ' · ' . e((string) $ev['address']) : '' ?></span>
        </a>
        <?php if ((int) $ev['is_published'] !== 1): ?><span class="pastille pastille--point pastille--neutre">Masqué</span><?php endif ?>
        <?php if ($ev['address'] && $ev['latitude'] === null && App\Lieux\Positions::pour((string) $ev['address']) === null): ?><span class="pastille pastille--point pastille--attention">Carte de la commune</span><?php endif ?>
        <a class="btn btn--neutre" href="<?= e($href) ?>"><?= Ui::icon('edit') ?>Modifier<span class="adm-masque"> <?= e((string) $ev['title']) ?></span></a>
      </li>
      <?php endforeach ?>
    </ul>
    <?php endif ?>
  </section>
  <div class="adm-carte adm-carte--simple adm-etat adm-etat--compact" data-filter-empty hidden><p class="adm-etat__titre">Aucun résultat</p><p class="adm-etat__texte">Aucun événement ne correspond à la recherche.</p><button class="btn btn--neutre" type="button" data-filter-reset>Effacer la recherche</button></div>
</div>
