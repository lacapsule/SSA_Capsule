<?php
declare(strict_types=1);

namespace App\Session;

use App\Core\Csrf;

/**
 * Adresses du minuteur : état (sans prolonger), prolongation (POST léger avec le jeton CSRF), expiration constatée
 * par le navigateur (redirection vers la connexion avec retour à la page).
 */
final class MinuteurController
{
    /** GET /session/etat : passif, ne compte pas comme une activité. */
    public static function etat(): void
    {
        $e = Minuteur::courant();
        $r = Minuteur::reglages();
        json_response($e['statut'] === 'valide'
            ? ['ok' => true, 'connecte' => true, 'restant' => $e['avant_inactivite'], 'avant_absolue' => $e['avant_absolue'], 'avertir' => $r['avertir']]
            : ['ok' => true, 'connecte' => false, 'connexion' => $r['connexion']]);
    }

    /**
     * POST /session/prolonger : la requête elle-même est l'activité (Minuteur::surveiller l'a comptée avant le routeur).
     * Session déjà expirée : 401 en JSON, sinon retour à la connexion. Sans JavaScript : retour à la page.
     */
    public static function prolonger(): void
    {
        $r = Minuteur::reglages();
        $retour = Minuteur::cheminLocal(is_string($_POST['retour'] ?? null) ? $_POST['retour'] : null) ?? '/';
        if (Minuteur::courant()['statut'] !== 'valide') {
            Csrf::checkOrigin();
            if (wants_json()) {
                json_response(['ok' => false, 'expiree' => true, 'connexion' => self::connexion($r, $retour), 'message' => 'Session expirée : reconnectez-vous.'], 401);
            }
            redirect(self::connexion($r, $retour));
        }
        Csrf::check();
        $e = Minuteur::courant();
        if (wants_json()) {
            json_response(['ok' => true, 'connecte' => true, 'restant' => $e['avant_inactivite'], 'avant_absolue' => $e['avant_absolue']]);
        }
        redirect($retour);
    }

    /**
     * GET /session/expiree?retour=/page : échéance atteinte dans le navigateur. Si un autre onglet a prolongé entre-temps,
     * retour à la page ; sinon fin de la session (moins de 15 s restantes : écart d'horloge toléré) et connexion.
     */
    public static function expiree(): void
    {
        $r = Minuteur::reglages();
        $retour = Minuteur::cheminLocal(is_string($_GET['retour'] ?? null) ? $_GET['retour'] : null) ?? '/';
        header('Cache-Control: no-store');
        $e = Minuteur::courant();
        if ($e['statut'] === 'valide' && $e['restant'] > 15) {
            redirect($retour);
        }
        if ($e['statut'] === 'valide') {
            Minuteur::expirer($e['avant_absolue'] <= $e['avant_inactivite'] ? 'absolue' : 'inactivite');
        }
        redirect(self::connexion($r, $retour));
    }

    private static function connexion(array $r, string $retour): string
    {
        return $r['connexion'] . (str_contains($r['connexion'], '?') ? '&' : '?') . 'retour=' . rawurlencode($retour);
    }
}
