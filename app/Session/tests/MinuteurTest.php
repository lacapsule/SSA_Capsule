<?php
declare(strict_types=1);

/*
 * Tests du minuteur de session (lanceur de modules/tests-et-ci-php : php tests/run.php minuteur).
 * Expiration par inactivité et absolue, requêtes passives, horodatages posés à la première requête, chemins de retour sûrs.
 */

use App\Session\Minuteur;

$r = Minuteur::reglages(['inactivite' => 1800, 'absolue' => 43200, 'avertir' => 120, 'cle_connexion' => 'admin_id',
    'cle_activite' => 'vu', 'cle_debut' => 'depuis', 'passifs' => ['/session/etat', '/session/expiree']]);
$t0 = 1_793_000_000;

return [
    'session anonyme : rien à contrôler, rien posé' => function () use ($r, $t0): void {
        $s = ['flash' => []];
        affirmer_egal(null, Minuteur::controler($s, '/admin/', $t0, $r));
        affirmer_egal(['flash' => []], $s);
        affirmer_egal('anonyme', Minuteur::etat($s, $t0, $r)['statut']);
    },

    'première requête connectée : horodatages posés' => function () use ($r, $t0): void {
        $s = ['admin_id' => 1];
        affirmer_egal(null, Minuteur::controler($s, '/admin/', $t0, $r));
        affirmer_egal($t0, $s['vu']);
        affirmer_egal($t0, $s['depuis']);
        affirmer_egal(1800, Minuteur::etat($s, $t0, $r)['restant']);
    },

    'expiration par inactivité, à la seconde près' => function () use ($r, $t0): void {
        $s = ['admin_id' => 1, 'vu' => $t0, 'depuis' => $t0];
        affirmer_egal(null, Minuteur::controler($s, '/admin/', $t0 + 1799, $r), '1 799 s : encore valide');
        affirmer_egal($t0 + 1799, $s['vu'], 'activité comptée');
        affirmer_egal('inactivite', Minuteur::controler($s, '/admin/', $t0 + 1799 + 1800, $r), '30 min sans requête : expirée');
        $e = Minuteur::etat(['admin_id' => 1, 'vu' => $t0, 'depuis' => $t0], $t0 + 1680, $r);
        affirmer_egal(['statut' => 'valide', 'restant' => 120, 'avant_inactivite' => 120, 'avant_absolue' => 41520], $e, 'fenêtre d\'avertissement');
    },

    'requête passive : ne prolonge pas' => function () use ($r, $t0): void {
        $s = ['admin_id' => 1, 'vu' => $t0, 'depuis' => $t0];
        Minuteur::controler($s, '/session/etat', $t0 + 1000, $r);
        affirmer_egal($t0, $s['vu'], 'état interrogé : activité inchangée');
        affirmer_egal('inactivite', Minuteur::controler($s, '/session/etat', $t0 + 1800, $r), 'interroger l\'état n\'empêche pas l\'expiration');
        $s = ['admin_id' => 1, 'vu' => $t0, 'depuis' => $t0];
        Minuteur::controler($s, '/session/prolonger', $t0 + 1000, $r);
        affirmer_egal($t0 + 1000, $s['vu'], 'prolonger : activité comptée');
    },

    'expiration absolue malgré une activité continue' => function () use ($r, $t0): void {
        $s = ['admin_id' => 1, 'vu' => $t0, 'depuis' => $t0];
        for ($t = $t0 + 600; $t < $t0 + 43200; $t += 600) {
            affirmer_egal(null, Minuteur::controler($s, '/admin/', $t, $r), 'actif toutes les 10 min');
        }
        affirmer_egal('absolue', Minuteur::controler($s, '/admin/', $t0 + 43200, $r), '12 h après la connexion');
        $e = Minuteur::etat(['admin_id' => 1, 'vu' => $t0 + 43000, 'depuis' => $t0], $t0 + 43100, $r);
        affirmer_egal(100, $e['restant'], 'l\'échéance absolue l\'emporte quand elle est plus proche');
        affirmer_egal(1700, $e['avant_inactivite']);
    },

    'horodatages illisibles : remplacés, pas acceptés' => function () use ($r, $t0): void {
        $s = ['admin_id' => 1, 'vu' => '9999999999', 'depuis' => 'x'];
        affirmer_egal(null, Minuteur::controler($s, '/admin/', $t0, $r));
        affirmer_egal($t0, $s['vu']);
        affirmer_egal($t0, $s['depuis']);
    },

    'réglages bornés et messages' => function (): void {
        $r = Minuteur::reglages(['inactivite' => 120, 'avertir' => 600, 'connexion' => '//autre.exemple/']);
        affirmer_egal(60, $r['avertir'], 'avertissement ramené à la moitié de l\'inactivité');
        affirmer_egal('/', $r['connexion'], 'connexion hors du site refusée');
        affirmer_contient('30 minutes', Minuteur::message('inactivite', Minuteur::reglages(['inactivite' => 1800])));
        affirmer_contient('12 heures', Minuteur::message('absolue', Minuteur::reglages(['absolue' => 43200])));
        affirmer_egal('1 h 30', Minuteur::duree(5400));
    },

    'chemin de retour : local seulement' => function (): void {
        affirmer_egal('/admin/fiches/12/?onglet=2', Minuteur::cheminLocal('/admin/fiches/12/?onglet=2'));
        foreach (['//autre.exemple', '/\\autre.exemple', "/\t/autre", 'https://autre.exemple/', 'admin/', ''] as $mauvais) {
            affirmer_egal(null, Minuteur::cheminLocal($mauvais), 'refusé : ' . json_encode($mauvais));
        }
    },
];
