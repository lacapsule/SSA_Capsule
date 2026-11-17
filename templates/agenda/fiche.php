<?php
/**
 * Événement, piste 2 (maquette docs/maquettes/agenda-fiche.md ; reprise demandée par Kouzia le 2026-11-17) : en-tête avec
 * la date en grand, l'heure, le public, « Ajouter à mon agenda » pour un événement à venir (fichier iCalendar ouvert par
 * l'iPhone et le Mac, liens Google Agenda et Outlook) et un personnage ; corps selon ce qui existe : description et lieu
 * côte à côte, ou le lieu seul en pleine largeur (adresse, itinéraire, carte de la commune lue dans l'adresse) ; voisins.
 * @var array $e @var string $path @var ?array $precedent @var ?array $suivant @var ?array $prochain
 */
use App\Agenda\Agenda;
use App\Agenda\Ics;
use App\Site\Dates;
use App\Site\Texte;

$icone = static fn (string $d, int $taille = 18): string => '<svg viewBox="0 0 24 24" width="' . $taille . '" height="' . $taille . '" aria-hidden="true" focusable="false"><path d="' . $d . '" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
$aVenir = (string) $e['ends_at'] >= (new \DateTimeImmutable('now', new \DateTimeZone('Europe/Paris')))->format('Y-m-d H:i');
$infos = [['icone' => 'M12 7v5l3 2M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18z', 'texte' => Dates::longue((string) $e['starts_at']) . ', ' . t('agenda.de_a', ['debut' => Dates::heure((string) $e['starts_at']), 'fin' => Dates::heure((string) $e['ends_at'])])]];
if (!empty($e['category']) && isset(Agenda::CATEGORIES[$e['category']])) {
    $infos[] = ['icone' => 'M17 20v-1a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v1M10 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM21 20v-1a4 4 0 0 0-3-3.9M16 3.1a4 4 0 0 1 0 7.8', 'texte' => t(Agenda::CATEGORIES[$e['category']])];
}
// Description dans le haut de page, sous la date et l'heure, avant l'ajout à l'agenda ou le prochain rendez-vous (demande
// de Kouzia, 2026-11-17 : toute l'information de l'événement avant de proposer autre chose).
$apres = !empty($e['description']) ? '<div class="page-head__description prose"' . langue_attr($e, 'description') . '>' . Texte::article((string) $e['description']) . '</div>' : '';
if ($aVenir) {
    $liens = Ics::liens($e);
    ob_start();
    partial('agenda/boutons-agendas', ['id' => 'ajout-agenda-titre', 'titre' => t('agenda.ajouter_agenda'), 'aide' => t('agenda.ajout_aide'), 'liens' => ['apple' => lurl('/agenda/' . $e['slug'] . '/') . 'evenement.ics', 'google' => $liens['google'], 'outlook' => $liens['outlook']]]); ?>
    <?php $apres .= (string) ob_get_clean();
} elseif (!empty($prochain) && $prochain['slug'] !== $e['slug']) {
    // Événement passé : le prochain rendez-vous à la place de l'ajout à l'agenda (revue visuelle du 2026-11-17).
    $pp = Dates::pastille((string) $prochain['starts_at']);
    ob_start(); ?>
    <a class="prochain-rdv" href="<?= e(lurl('/agenda/' . $prochain['slug'] . '/')) ?>">
      <span class="prochain-rdv__date" aria-hidden="true"><strong><?= e($pp['jour']) ?></strong><span><?= e($pp['mois']) ?></span></span>
      <span class="prochain-rdv__texte"><span class="prochain-rdv__sur"><?= e(t('agenda.prochain_rdv')) ?></span><span class="prochain-rdv__titre"><?= e((string) $prochain['title']) ?></span><span class="prochain-rdv__quand"><?= e(Dates::longue((string) $prochain['starts_at']) . ', ' . Dates::heure((string) $prochain['starts_at'])) ?></span></span>
    </a>
    <?php $apres .= (string) ob_get_clean();
}
// Lieu à droite du titre (demande de Kouzia, 2026-11-17 : information de premier rang, visible sans défiler) : carte
// du quartier au point exact quand l'IGN l'a reconnu, sinon de la commune, puis l'itinéraire.
$cote = '';
if (!empty($e['address'])) {
    $position = \App\Lieux\Positions::pour((string) $e['address']);
    $vers = $position !== null ? 'https://www.openstreetmap.org/directions?to=' . $position['lat'] . ',' . $position['lon'] : 'https://www.openstreetmap.org/search?query=' . rawurlencode((string) $e['address']);
    ob_start(); ?>
    <div class="evenement-lieu">
      <h2 class="evenement-lieu__titre"><?= $icone('M12 21s-7-6.2-7-11.5A7 7 0 0 1 19 9.5C19 14.8 12 21 12 21zM12 12a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5z', 20) ?><span<?= langue_attr($e, 'address') ?>><?= e((string) $e['address']) ?></span></h2>
      <?php partial('lieux/carte-lieu', ['adresse' => (string) $e['address'], 'libelle' => trim(explode(' - ', (string) $e['address'])[0]), 'titre' => t('carte.lieu_sur_carte')]) ?>
      <p class="evenement-lieu__action"><a class="button button--ghost" href="<?= e($vers) ?>" rel="noopener"><?= $icone('M3 11l18-8-8 18-2-8-8-2z') ?><?= e(t('agenda.itineraire')) ?></a></p>
    </div>
    <?php $cote = (string) ob_get_clean();
}
partial('site/haut', ['chemin' => $path, 'titre' => (string) $e['title'], 'chapo' => '', 'parent' => ['chemin' => '/agenda/', 'libelle' => t('menu.agenda')], 'date' => (string) $e['starts_at'], 'surTitre' => $aVenir ? t('agenda.a_venir') : t('agenda.evenement_passe'), 'infos' => $infos, 'apres' => $apres, 'cote' => $cote, 'classe' => 'page-head--evenement' . ($aVenir ? '' : ' page-head--passe')]);
?>
<?php partial('site/voisins', ['nature' => 'evenement', 'precedent' => $precedent ? ['titre' => $precedent['title'], 'chemin' => '/agenda/' . $precedent['slug'] . '/', 'date' => (string) $precedent['starts_at']] : null, 'suivant' => $suivant ? ['titre' => $suivant['title'], 'chemin' => '/agenda/' . $suivant['slug'] . '/', 'date' => (string) $suivant['starts_at']] : null, 'rubrique' => ['chemin' => '/agenda/', 'libelle' => t('accueil.tout_agenda')]]) ?>
