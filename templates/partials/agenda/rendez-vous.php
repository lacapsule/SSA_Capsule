<?php
/** Rendez-vous en encadré (brique sections/prochains-rendez-vous) : jour en très grand, mois et année, titre en lien
 * étiré vers la fiche, date longue, heure, lieu, public. @var array<string, mixed> $e */
use App\Agenda\Agenda;
use App\Site\Dates;

$p = Dates::pastille((string) $e['starts_at']);
?>
<li class="rendez-vous__item">
  <time class="rendez-vous__date" datetime="<?= e(Dates::iso((string) $e['starts_at'])) ?>"><strong><?= e($p['jour']) ?></strong><span><?= e($p['mois']) ?> <?= e(substr((string) $e['starts_at'], 0, 4)) ?></span></time>
  <div>
    <h3 class="rendez-vous__titre"><a href="<?= e(lurl('/agenda/' . $e['slug'] . '/')) ?>"<?= langue_attr($e, 'title') ?>><?= e((string) $e['title']) ?></a></h3>
    <p class="rendez-vous__infos"><?= e(Dates::longue((string) $e['starts_at'])) ?>, <?= e(Dates::heure((string) $e['starts_at'])) ?><?php if (!empty($e['address'])): ?> · <span<?= langue_attr($e, 'address') ?>><?= e((string) $e['address']) ?></span><?php endif ?></p>
    <?php if (!empty($e['category']) && isset(Agenda::CATEGORIES[$e['category']])): ?><p class="etiquette etiquette--<?= e((string) $e['category']) ?>"><?= e(t(Agenda::CATEGORIES[$e['category']])) ?></p><?php endif ?>
    <span class="lien-fleche rendez-vous__voir" aria-hidden="true"><?= e(t('agenda.voir')) ?><svg viewBox="0 0 16 16" width="16" height="16" focusable="false"><path d="M3 8h10M9 4l4 4-4 4" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
  </div>
</li>
