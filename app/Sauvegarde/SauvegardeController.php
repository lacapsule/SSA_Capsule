<?php
declare(strict_types=1);

namespace App\Sauvegarde;

use App\Admin\Admin;
use App\Core\Session;
use App\Core\View;

/**
 * Écran d'administration des sauvegardes (administrateurs seulement) : état, liste, Lancer maintenant, Vérifier,
 * Restaurer (si config('sauvegarde.web_restauration')). Volontairement aucun téléchargement : une sauvegarde contient
 * toute la base ; elle se récupère en SFTP ou par la copie hors serveur (scripts/sauvegarde.php copier).
 */
final class SauvegardeController
{
    public static function index(): void
    {
        Admin::requireAdmin();
        $erreurCle = null;
        try {
            $s = Sauvegarde::depuisConfig();
        } catch (\Throwable $e) {
            $s = null;
            $erreurCle = $e->getMessage();
        }
        $liste = $s?->lister() ?? [];
        $verification = $s?->derniereVerification();
        $alertes = [];
        if ($erreurCle !== null) {
            $alertes[] = ['erreur', $erreurCle];
        } elseif (!$s->estChiffree()) {
            $alertes[] = [is_local() ? 'attention' : 'erreur', 'BACKUP_KEY absente du .env : les sauvegardes ne peuvent pas être chiffrées.'];
        }
        $maxS = (int) config('sauvegarde.alerte_sauvegarde_jours', 2);
        if ($s !== null && $liste === []) {
            $alertes[] = ['attention', 'Aucune sauvegarde pour l\'instant. Programmez « php scripts/sauvegarde.php creer » chaque nuit.'];
        } elseif ($liste !== [] && strtotime($liste[0]['date']) < time() - $maxS * 86400) {
            $alertes[] = ['erreur', "La dernière sauvegarde date de plus de $maxS jours : la tâche planifiée fonctionne-t-elle ?"];
        }
        if ($verification !== null && !$verification['ok']) {
            $alertes[] = ['erreur', 'La dernière vérification a échoué : ' . implode(' ; ', $verification['problemes'])];
        }
        View::render('admin/sauvegarde/index', [
            'title' => 'Sauvegardes',
            'styles' => ['/assets/css/sauvegarde.css'],
            'scripts' => ['/assets/js/sauvegarde.js'],
            'sauvegardes' => $liste,
            'verification' => $verification,
            'alertes' => $alertes,
            'chiffree' => $s?->estChiffree() ?? false,
            'distante' => (string) ($_ENV['BACKUP_REMOTE'] ?? '') !== '',
            'retention' => [(int) config('sauvegarde.retention_jours', 30), (int) config('sauvegarde.retention_mois', 12)],
            'actions' => (bool) config('sauvegarde.web_actions', true),
            'restauration' => (bool) config('sauvegarde.web_restauration', false),
        ], (string) config('admin.layout', 'admin/layout'));
    }

    public static function lancer(): void
    {
        Admin::action();
        Admin::requireAdmin();
        self::exigerActions();
        try {
            @set_time_limit(300);
            $r = Sauvegarde::depuisConfig()->creer('manuelle');
            Session::flash('Sauvegarde créée : ' . $r['nom'] . ' (' . Sauvegarde::tailleLisible($r['taille']) . ').');
        } catch (\Throwable $e) {
            error_log('Sauvegarde manuelle : ' . $e->getMessage());
            Session::flash('Sauvegarde impossible : ' . $e->getMessage(), 'error');
        }
        redirect(Admin::url('sauvegardes/'));
    }

    public static function verifier(): void
    {
        Admin::action();
        Admin::requireAdmin();
        self::exigerActions();
        try {
            @set_time_limit(300);
            $s = Sauvegarde::depuisConfig();
            $nom = (string) ($_POST['fichier'] ?? '');
            $f = $nom !== '' ? $s->chemin($nom) : ($s->plusRecente()['fichier'] ?? null);
            if ($f === null) {
                throw new \RuntimeException('Aucune sauvegarde à vérifier.');
            }
            $r = $s->verifier($f);
            $s->memoriserVerification($r);
            $r['ok']
                ? Session::flash('Sauvegarde vérifiée : ' . basename($f) . ' est lisible et complète.')
                : Session::flash('Sauvegarde inutilisable : ' . implode(' ; ', $r['problemes']), 'error');
        } catch (\Throwable $e) {
            Session::flash('Vérification impossible : ' . $e->getMessage(), 'error');
        }
        redirect(Admin::url('sauvegardes/'));
    }

    /** Page de confirmation sans JavaScript (le dialogue de l'écran fait la même chose). */
    public static function confirmer(): void
    {
        Admin::requireAdmin();
        self::exigerRestauration();
        $s = Sauvegarde::depuisConfig();
        $nom = (string) ($_GET['fichier'] ?? '');
        try {
            $s->chemin($nom);
        } catch (\RuntimeException) {
            abort(404);
        }
        View::render('admin/sauvegarde/confirmer', ['title' => 'Restaurer une sauvegarde', 'nom' => $nom], (string) config('admin.layout', 'admin/layout'));
    }

    public static function restaurer(): void
    {
        $admin = Admin::action();
        Admin::requireAdmin();
        self::exigerRestauration();
        if (trim((string) ($_POST['confirmation'] ?? '')) !== 'RESTAURER') {
            Session::flash('Restauration annulée : saisissez RESTAURER pour confirmer.', 'error');
            redirect(Admin::url('sauvegardes/'));
        }
        try {
            @set_time_limit(600);
            $s = Sauvegarde::depuisConfig();
            $res = $s->restaurer($s->chemin((string) ($_POST['fichier'] ?? '')));
            error_log('Sauvegarde restaurée par le compte ' . $admin['id'] . ' : ' . basename((string) $res['verification']['fichier']));
            Session::flash('Base restaurée depuis ' . basename((string) $res['verification']['fichier'])
                . '. Sauvegarde de sécurité : ' . basename((string) $res['securite']) . '. Lancez les migrations si la sauvegarde est ancienne.');
        } catch (\Throwable $e) {
            Session::flash('Restauration impossible : ' . $e->getMessage(), 'error');
        }
        redirect(Admin::url('sauvegardes/'));
    }

    private static function exigerActions(): void
    {
        if (!config('sauvegarde.web_actions', true)) {
            abort(404);
        }
    }

    private static function exigerRestauration(): void
    {
        if (!config('sauvegarde.web_restauration', false)) {
            abort(404);
        }
    }
}
