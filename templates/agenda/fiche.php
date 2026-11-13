<?php
/** Événement (maquette docs/maquettes/agenda-fiche.md). @var array $e @var string $path @var ?array $precedent @var ?array $suivant */
use App\Agenda\Agenda;
use App\Site\Dates;
use App\Site\Texte;

partial('site/haut', ['chemin' => $path, 'titre' => (string) $e['title'], 'chapo' => '', 'parent' => ['chemin' => '/agenda/', 'libelle' => t('menu.agenda')]]);
?>
<section class="section">
  <div class="container prose">
    <dl class="infos-pratiques">
      <div><dt><?= e(t('agenda.quand')) ?></dt><dd><time datetime="<?= e(Dates::iso((string) $e['starts_at'])) ?>"><?= e(Dates::longue((string) $e['starts_at'])) ?></time>, <?= e(t('agenda.de_a', ['debut' => Dates::heure((string) $e['starts_at']), 'fin' => Dates::heure((string) $e['ends_at'])])) ?></dd></div>
      <?php if (!empty($e['address'])): ?><div><dt><?= e(t('agenda.ou')) ?></dt><dd<?= langue_attr($e, 'address') ?>><?= e((string) $e['address']) ?></dd></div><?php endif ?>
      <?php if (!empty($e['category']) && isset(Agenda::CATEGORIES[$e['category']])): ?><div><dt><?= e(t('agenda.pour_qui')) ?></dt><dd><?= e(t(Agenda::CATEGORIES[$e['category']])) ?></dd></div><?php endif ?>
    </dl>
    <?php if (!empty($e['address'])): ?>
    <p><a class="button button--ghost" href="https://www.openstreetmap.org/search?query=<?= e(rawurlencode((string) $e['address'])) ?>" rel="noopener"><?= e(t('agenda.itineraire')) ?></a></p>
    <?php endif ?>
    <?php if (!empty($e['description'])): ?><div<?= langue_attr($e, 'description') ?>><?= Texte::paragraphes((string) $e['description']) ?></div><?php endif ?>
  </div>
</section>
<?php partial('site/voisins', ['nature' => 'evenement', 'precedent' => $precedent ? ['titre' => $precedent['title'], 'chemin' => '/agenda/' . $precedent['slug'] . '/'] : null, 'suivant' => $suivant ? ['titre' => $suivant['title'], 'chemin' => '/agenda/' . $suivant['slug'] . '/'] : null, 'rubrique' => ['chemin' => '/agenda/', 'libelle' => t('accueil.tout_agenda')]]) ?>
