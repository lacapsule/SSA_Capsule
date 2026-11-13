<?php
/** Carte d'événement : pastille de date jaune, titre en lien, heure et lieu. @var array<string, mixed> $e */
use App\Agenda\Agenda;
use App\Site\Dates;

$p = Dates::pastille((string) $e['starts_at']);
?>
<li class="evenement">
  <time class="evenement__date pastille-date" datetime="<?= e(Dates::iso((string) $e['starts_at'])) ?>"><span class="pastille-date__jour"><?= e($p['jour']) ?></span> <span class="pastille-date__mois"><?= e($p['mois']) ?></span></time>
  <div class="evenement__corps">
    <h3 class="evenement__titre"><a href="<?= e(lurl('/agenda/' . $e['slug'] . '/')) ?>"<?= langue_attr($e, 'title') ?>><?= e((string) $e['title']) ?></a></h3>
    <p class="evenement__infos"><?= e(Dates::longue((string) $e['starts_at'])) ?>, <?= e(Dates::heure((string) $e['starts_at'])) ?><?php if (!empty($e['address'])): ?> · <span<?= langue_attr($e, 'address') ?>><?= e((string) $e['address']) ?></span><?php endif ?></p>
    <?php if (!empty($e['description']) && trim((string) $e['description']) !== trim((string) ($e['address'] ?? ''))): ?><div class="evenement__texte"<?= langue_attr($e, 'description') ?>><?= App\Site\Texte::paragraphes((string) $e['description']) ?></div><?php endif ?>
    <?php if (!empty($e['category']) && isset(Agenda::CATEGORIES[$e['category']])): ?><p class="evenement__public"><?= e(t(Agenda::CATEGORIES[$e['category']])) ?></p><?php endif ?>
  </div>
</li>
