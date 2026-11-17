<?php
/**
 * Trois boutons d'agenda à égalité (demande de Kouzia, 2026-11-17), chacun avec l'icône de l'application : Apple
 * (fichier iCalendar ouvert directement par l'iPhone, l'iPad et le Mac), Google Agenda, Outlook. Simples liens : aucun
 * script, rien n'est envoyé avant le clic. Fiche d'un événement (ajout) et page Agenda (abonnement au calendrier).
 * Icônes : Font Awesome Free 6.7.2 (brands apple, google, microsoft), licence CC BY 4.0, https://fontawesome.com/license/free
 * @var array{apple: string, google: string, outlook: string} $liens @var string $titre @var string $aide @var string $id
 */
$icones = [
    'apple' => ['0 0 384 512', 'M318.7 268.7c-.2-36.7 16.4-64.4 50-84.8-18.8-26.9-47.2-41.7-84.7-44.6-35.5-2.8-74.3 20.7-88.5 20.7-15 0-49.4-19.7-76.4-19.7C63.3 141.2 4 184.8 4 273.5q0 39.3 14.4 81.2c12.8 36.7 59 126.7 107.2 125.2 25.2-.6 43-17.9 75.8-17.9 31.8 0 48.3 17.9 76.4 17.9 48.6-.7 90.4-82.5 102.6-119.3-65.2-30.7-61.7-90-61.7-91.9zm-56.6-164.2c27.3-32.4 24.8-61.9 24-72.5-24.1 1.4-52 16.4-67.9 34.9-17.5 19.8-27.8 44.3-25.6 71.9 26.1 2 49.9-11.4 69.5-34.3z'],
    'google' => ['0 0 488 512', 'M488 261.8C488 403.3 391.1 504 248 504 110.8 504 0 393.2 0 256S110.8 8 248 8c66.8 0 123 24.5 166.3 64.9l-67.5 64.9C258.5 52.6 94.3 116.6 94.3 256c0 86.5 69.1 156.6 153.7 156.6 98.2 0 135-70.4 140.8-106.9H248v-85.3h236.1c2.3 12.7 3.9 24.9 3.9 41.4z'],
    'outlook' => ['0 0 448 512', 'M0 32h214.6v214.6H0V32zm233.4 0H448v214.6H233.4V32zM0 265.4h214.6V480H0V265.4zm233.4 0H448V480H233.4V265.4z'],
];
$noms = ['apple' => t('agenda.app_apple'), 'google' => t('agenda.app_google'), 'outlook' => t('agenda.app_outlook')];
?>
<div class="ajout-agenda" role="group" aria-labelledby="<?= e($id) ?>">
  <p class="ajout-agenda__titre" id="<?= e($id) ?>"><?= e($titre) ?></p>
  <ul class="ajout-agenda__liste">
    <?php foreach (['apple', 'google', 'outlook'] as $app): if (($liens[$app] ?? '') === '') continue; ?>
    <li><a class="ajout-agenda__bouton" href="<?= e($liens[$app]) ?>"<?= preg_match('#^https?://#', $liens[$app]) === 1 ? ' rel="noopener"' : '' ?>><svg viewBox="<?= $icones[$app][0] ?>" width="20" height="20" aria-hidden="true" focusable="false"><path d="<?= $icones[$app][1] ?>" fill="currentColor"/></svg><?= e($noms[$app]) ?></a></li>
    <?php endforeach ?>
  </ul>
  <?php if (($aide ?? '') !== ''): ?><p class="ajout-agenda__aide"><?= e($aide) ?></p><?php endif ?>
</div>
