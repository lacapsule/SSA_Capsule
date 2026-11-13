<?php
/** Agenda (maquette docs/maquettes/agenda.md). @var array $t @var string $path @var ?string $categorie @var list<array> $evenements @var list<array> $passes */
use App\Agenda\Agenda;

partial('site/haut', ['chemin' => $path, 'titre' => $t['titre'], 'chapo' => $t['chapo']]);
?>
<section class="section" aria-labelledby="a-venir-titre">
  <div class="container">
    <nav class="filtres" aria-label="<?= e(t('agenda.filtrer')) ?>">
      <ul>
        <li><a href="<?= e(lurl('/agenda/')) ?>"<?= $categorie === null ? ' aria-current="page"' : '' ?>><?= e(t('agenda.tous')) ?></a></li>
        <?php foreach (Agenda::CATEGORIES as $cle => $libelle): ?>
        <li><a href="<?= e(lurl('/agenda/')) ?>?public=<?= e($cle) ?>"<?= $categorie === $cle ? ' aria-current="page"' : '' ?>><?= e(t($libelle)) ?></a></li>
        <?php endforeach ?>
      </ul>
    </nav>
    <h2 id="a-venir-titre"><?= e(t('agenda.a_venir')) ?></h2>
    <?php if ($evenements === []): ?>
    <p class="etat-vide"><?= e(t('agenda.aucun')) ?></p>
    <?php else: ?>
    <?php // Regroupés par mois : une longue liste reste lisible (12 événements et plus). ?>
    <?php foreach (Agenda::parMois($evenements) as $mois => $liste): ?>
    <h3 class="agenda-mois"><?= e($mois) ?></h3>
    <ul class="evenements"><?php foreach ($liste as $e) partial('agenda/carte', ['e' => $e]) ?></ul>
    <?php endforeach ?>
    <?php endif ?>
    <?php if ($passes !== []): ?>
    <details class="passes">
      <summary><?= e(t('agenda.passes')) ?></summary>
      <ul class="evenements"><?php foreach ($passes as $e) partial('agenda/carte', ['e' => $e]) ?></ul>
    </details>
    <?php endif ?>
  </div>
</section>
