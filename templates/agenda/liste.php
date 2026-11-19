<?php
/**
 * Agenda, piste 2 (maquette docs/maquettes/agenda.md) : haut de page court (les rendez-vous à venir d'abord, demande
 * de Kouzia du 2026-11-17), filtre par public,
 * événements à venir en encadrés (date en très grand), puis les cinq événements passés les plus récents et tous les
 * autres par année (volets repliés) et par mois, du plus récent au plus ancien ; appel au contact avec le personnage.
 * @var array $t @var string $path @var ?string $categorie @var list<array> $evenements @var list<array> $passes
 */
use App\Agenda\Agenda;
use App\Site\Dates;
use App\Site\Site;

partial('site/haut', ['chemin' => $path, 'titre' => $t['titre'], 'chapo' => $t['rythme_texte'] . ' ' . $t['chapo']]); // rythme dans le haut de page (demande de Kouzia, 2026-11-19)
$fleche = '<svg class="agenda-passe__fleche" viewBox="0 0 16 16" width="16" height="16" aria-hidden="true" focusable="false"><path d="M3 8h10M9 4l4 4-4 4" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>';
$ligne = static function (array $e) use ($fleche): void {
    $p = Dates::pastille((string) $e['starts_at']); ?>
          <li class="agenda-passe">
            <time class="agenda-passe__date" datetime="<?= e(Dates::iso((string) $e['starts_at'])) ?>"><strong><?= e($p['jour']) ?></strong> <?= e($p['mois']) ?></time>
            <span class="agenda-passe__corps"><a href="<?= e(lurl('/agenda/' . $e['slug'] . '/')) ?>"<?= langue_attr($e, 'title') ?>><?= e((string) $e['title']) ?></a><?php if (!empty($e['address'])): ?><span class="agenda-passe__lieu"<?= langue_attr($e, 'address') ?>><?= e((string) $e['address']) ?></span><?php endif ?></span>
            <?= $fleche ?>
          </li>
<?php };
$annees = [];
foreach ($passes as $e) {
    $annees[substr((string) $e['starts_at'], 0, 4)][] = $e;
}
?>
<section class="section agenda" aria-labelledby="a-venir-titre">
  <div class="container">
    <div class="agenda__tete">
      <h2 id="a-venir-titre"><?= e(t('agenda.a_venir')) ?></h2>
      <nav class="filtres" aria-label="<?= e(t('agenda.filtrer')) ?>">
        <ul>
          <li><a href="<?= e(lurl('/agenda/')) ?>#a-venir-titre"<?= $categorie === null ? ' aria-current="page"' : '' ?>><?= e(t('agenda.tous')) ?></a></li>
          <?php foreach (Agenda::CATEGORIES as $cle => $libelle): ?>
          <li><a href="<?= e(lurl('/agenda/')) ?>?public=<?= e($cle) ?>#a-venir-titre"<?= $categorie === $cle ? ' aria-current="page"' : '' ?>><?= e(t($libelle)) ?></a></li>
          <?php endforeach ?>
        </ul>
      </nav>
    </div>
    <?php $abonnements = App\Agenda\Ics::abonnements(); $abonner = array_filter($abonnements) !== []; ?>
    <div class="agenda__a-venir<?= $abonner ? ' agenda__a-venir--abonnement' : '' ?>">
      <?php if ($evenements === []): ?>
      <p class="etat-vide"><?= e(t('agenda.aucun')) ?></p>
      <?php else: ?>
      <ul class="rendez-vous__liste"><?php foreach ($evenements as $e) partial('agenda/rendez-vous', ['e' => $e]) ?></ul>
      <?php endif ?>
      <?php if ($abonner): // à côté des rendez-vous à venir, à la place de la phrase sur le rythme (demande de Kouzia, 2026-11-19) ?>
      <div class="agenda__abonnement">
        <h3><?= e($t['abonnement_titre']) ?></h3>
        <p><?= e($t['abonnement_texte']) ?></p>
        <?php partial('agenda/boutons-agendas', ['id' => 'abonnement-titre', 'titre' => $t['abonnement_choix'], 'aide' => $t['abonnement_aide'], 'liens' => $abonnements]) ?>
      </div>
      <?php endif ?>
    </div>
  </div>
</section>

<?php if ($annees !== []): ?>
<section class="section agenda-passes" aria-labelledby="passes-titre">
  <div class="container">
    <h2 id="passes-titre"><?= e(t('agenda.passes')) ?> <span class="agenda-passes__nombre">(<?= count($passes) ?>)</span></h2>
    <h3 class="agenda-passes__titre agenda-passes__recents"><?= e($t['derniers_passes']) ?></h3>
    <ul class="agenda-passes__liste agenda-passes__liste--recents">
      <?php foreach (array_slice($passes, 0, 5) as $e) $ligne($e) ?>
    </ul>
    <?php foreach ($annees as $annee => $deLAnnee): ?>
    <details class="agenda-passes__annee">
      <summary><svg class="agenda-passes__chevron" viewBox="0 0 16 16" width="20" height="20" aria-hidden="true" focusable="false"><path d="M6 3l5 5-5 5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg><span class="agenda-passes__annee-titre"><?= e((string) $annee) ?></span> <span class="agenda-passes__nombre"><?= e(tn('agenda.n_evenements', count($deLAnnee))) ?></span></summary>
      <?php foreach (Agenda::parMois($deLAnnee) as $mois => $liste): ?>
      <div class="agenda-passes__mois">
        <h3 class="agenda-passes__titre"><?= e($mois) ?></h3>
        <ul class="agenda-passes__liste">
          <?php foreach ($liste as $e) $ligne($e) ?>
        </ul>
      </div>
      <?php endforeach ?>
    </details>
    <?php endforeach ?>
  </div>
</section>
<?php endif ?>

<?php partial('site/appel', ['titre' => $t['appel_titre'], 'texte' => $t['appel_texte'], 'actions' => [['href' => '/contact/', 'libelle' => t('contact.nous_contacter')]], 'image' => '/assets/img/personnages/agenda.svg']) ?>
