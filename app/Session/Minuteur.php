<?php
declare(strict_types=1);

namespace App\Session;

use App\Core\Session;

/**
 * Expiration de session côté serveur : par inactivité (dernière activité horodatée en session) et absolue (durée
 * maximale depuis la connexion). Appelé une fois par requête, avant le routeur :
 *   App\Session\Minuteur::surveiller(current_path());
 * Une session expirée est vidée et reçoit un nouvel identifiant ; la garde d'administration (ou de l'espace membre)
 * voit alors une personne déconnectée et la renvoie vers la connexion. Les requêtes « passives » (état interrogé
 * par le script) ne prolongent rien. Aucune table : deux horodatages en session.
 */
final class Minuteur
{
    /** Heure courante, remplaçable dans les tests. @var (callable(): int)|null */
    public static $horloge = null;

    public const MESSAGES = [
        'inactivite' => "Votre session a expiré après %s sans activité. Reconnectez-vous\u{00A0}: vous reviendrez sur la page où vous étiez.",
        'absolue' => 'Votre session a atteint sa durée maximale (%s). Reconnectez-vous pour continuer.',
    ];

    public static function maintenant(): int
    {
        return self::$horloge !== null ? (int) (self::$horloge)() : time();
    }

    /** @return array{inactivite: int, absolue: int, avertir: int, cle_connexion: string, cle_activite: string, cle_debut: string, connexion: string, deconnexion: string, passifs: list<string>, avis: string, prolonger_sur_activite: bool} */
    public static function reglages(?array $c = null): array
    {
        $c ??= (array) config('session_minuteur', []);
        $r = [
            'inactivite' => max(60, (int) ($c['inactivite'] ?? 1800)),
            'absolue' => max(60, (int) ($c['absolue'] ?? 43200)),
            'avertir' => max(20, (int) ($c['avertir'] ?? 120)),
            'cle_connexion' => (string) ($c['cle_connexion'] ?? 'admin_id'),
            'cle_activite' => (string) ($c['cle_activite'] ?? 'minuteur_activite'),
            'cle_debut' => (string) ($c['cle_debut'] ?? 'minuteur_debut'),
            'connexion' => self::cheminLocal((string) ($c['connexion'] ?? '/admin/connexion/')) ?? '/',
            'deconnexion' => self::cheminLocal((string) ($c['deconnexion'] ?? '')) ?? '',
            'passifs' => array_values(array_map('strval', (array) ($c['passifs'] ?? ['/session/etat', '/session/expiree']))),
            'avis' => ($c['avis'] ?? 'flash') === 'partiel' ? 'partiel' : 'flash',
            'prolonger_sur_activite' => (bool) ($c['prolonger_sur_activite'] ?? false),
        ];
        // L'avertissement doit tenir dans la durée d'inactivité.
        $r['avertir'] = min($r['avertir'], intdiv($r['inactivite'], 2));
        return $r;
    }

    /**
     * État d'une session, sans effet. Fonction pure (testable).
     * @param array<string, mixed> $session
     * @param array{inactivite: int, absolue: int, cle_connexion: string, cle_activite: string, cle_debut: string} $r
     * @return array{statut: string, restant: int, avant_inactivite: int, avant_absolue: int}
     *   statut : 'anonyme', 'valide', 'inactivite' ou 'absolue' ; restant : secondes avant la première échéance
     */
    public static function etat(array $session, int $maintenant, array $r): array
    {
        if (!isset($session[$r['cle_connexion']])) {
            return ['statut' => 'anonyme', 'restant' => 0, 'avant_inactivite' => 0, 'avant_absolue' => 0];
        }
        $activite = is_int($session[$r['cle_activite']] ?? null) ? $session[$r['cle_activite']] : $maintenant;
        $debut = is_int($session[$r['cle_debut']] ?? null) ? $session[$r['cle_debut']] : $maintenant;
        $avantInactivite = $activite + $r['inactivite'] - $maintenant;
        $avantAbsolue = $debut + $r['absolue'] - $maintenant;
        $statut = $avantAbsolue <= 0 ? 'absolue' : ($avantInactivite <= 0 ? 'inactivite' : 'valide');
        return [
            'statut' => $statut,
            'restant' => max(0, min($avantInactivite, $avantAbsolue)),
            'avant_inactivite' => max(0, $avantInactivite),
            'avant_absolue' => max(0, $avantAbsolue),
        ];
    }

    /**
     * Contrôle d'une requête sur le tableau de session (sans effet hors de lui) : horodatages posés à la première
     * requête connectée, activité mise à jour sauf pour un chemin passif. Renvoie la raison si la session est échue.
     * @param array<string, mixed> $session
     */
    public static function controler(array &$session, string $chemin, int $maintenant, array $r): ?string
    {
        $etat = self::etat($session, $maintenant, $r);
        if ($etat['statut'] === 'anonyme') {
            return null;
        }
        if ($etat['statut'] !== 'valide') {
            return $etat['statut'];
        }
        if (!is_int($session[$r['cle_debut']] ?? null)) {
            $session[$r['cle_debut']] = $maintenant;
        }
        if (!in_array($chemin, $r['passifs'], true) || !is_int($session[$r['cle_activite']] ?? null)) {
            $session[$r['cle_activite']] = $maintenant;
        }
        return null;
    }

    /** À chaque requête, avant le routeur. Renvoie la raison d'une expiration constatée maintenant, sinon null. */
    public static function surveiller(string $chemin): ?string
    {
        Session::resume();
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return null;
        }
        $r = self::reglages();
        $raison = self::controler($_SESSION, $chemin, self::maintenant(), $r);
        if ($raison !== null) {
            self::expirer($raison);
        }
        return $raison;
    }

    /** À la connexion, après Session::regenerate() : les deux horloges repartent. */
    public static function demarrer(): void
    {
        Session::start();
        $r = self::reglages();
        $_SESSION[$r['cle_debut']] = $_SESSION[$r['cle_activite']] = self::maintenant();
    }

    /** Session vidée, nouvel identifiant (l'ancien ne vaut plus rien), message gardé pour la page de connexion. */
    public static function expirer(string $raison): void
    {
        Session::start();
        $r = self::reglages();
        $_SESSION = [];
        Session::regenerate();
        $message = self::message($raison, $r);
        if ($r['avis'] === 'flash') {
            Session::flash($message, 'info');
        } else {
            $_SESSION['minuteur_expiration'] = $message;
        }
    }

    public static function message(string $raison, array $r): string
    {
        $duree = $raison === 'absolue' ? $r['absolue'] : $r['inactivite'];
        return sprintf(self::MESSAGES[$raison] ?? self::MESSAGES['inactivite'], self::duree($duree));
    }

    /** Message d'expiration à afficher une fois (avis 'partiel'), puis oublié. */
    public static function messageExpiration(): ?string
    {
        Session::resume();
        $m = $_SESSION['minuteur_expiration'] ?? null;
        unset($_SESSION['minuteur_expiration']);
        return is_string($m) ? $m : null;
    }

    /** État de la session courante (sans effet), pour le partiel et l'adresse /session/etat. */
    public static function courant(): array
    {
        Session::resume();
        return self::etat(session_status() === PHP_SESSION_ACTIVE ? $_SESSION : [], self::maintenant(), self::reglages());
    }

    /** « 30 minutes », « 12 heures », « 1 h 30 ». */
    public static function duree(int $secondes): string
    {
        $min = intdiv($secondes, 60);
        if ($min < 60) {
            return $min . ($min > 1 ? ' minutes' : ' minute');
        }
        $h = intdiv($min, 60);
        $m = $min % 60;
        return $m === 0 ? $h . ($h > 1 ? ' heures' : ' heure') : $h . ' h ' . str_pad((string) $m, 2, '0', STR_PAD_LEFT);
    }

    /** Chemin local sûr (un seul « / » en tête, sans contrôle ni barre inverse), sinon null. */
    public static function cheminLocal(?string $chemin): ?string
    {
        if ($chemin === null || $chemin === '' || strlen($chemin) > 500 || !str_starts_with($chemin, '/')
            || preg_match('#^/[/\\\\]|[\x00-\x1F\x7F\\\\]#', $chemin) === 1) {
            return null;
        }
        return $chemin;
    }
}
