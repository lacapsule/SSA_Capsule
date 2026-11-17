<?php
declare(strict_types=1);

namespace App\Agenda;

use App\Core\Db;
use App\Langue\Langue;
use App\Site\Site;

/**
 * Agenda au format iCalendar (RFC 5545), décision du 2026-11-17 : « Ajouter à mon agenda » pour un événement
 * (/agenda/<slug>/evenement.ics) et abonnement au calendrier complet (/agenda.ics), lus par tous les agendas de téléphone.
 * Heures saisies à l'heure de Paris, écrites en UTC ; textes échappés et lignes repliées à 75 octets.
 */
final class Ics
{
    private const CHAMPS = 'id, title, starts_at, ends_at, address, description, slug';

    public static function evenement(string $slug): void
    {
        $e = Db::run('SELECT ' . self::CHAMPS . ' FROM events WHERE is_published = 1 AND slug = ?', [$slug])->fetch();
        if ($e === false) {
            abort(404);
        }
        // Affiché (inline) et non téléchargé : l'iPhone et le Mac ouvrent alors directement « Ajouter au calendrier ».
        self::envoyer([$e], $slug . '.ics', false);
    }

    public static function calendrier(): void
    {
        self::envoyer(Db::run('SELECT ' . self::CHAMPS . ' FROM events WHERE is_published = 1 ORDER BY starts_at')->fetchAll(), 'agenda-ssa-pays-de-morlaix.ics', false);
    }

    /**
     * Liens « ajouter » de Google Agenda et d'Outlook.com, préremplis (simples liens : aucun script, aucune donnée envoyée
     * tant que la personne ne clique pas). Pour Android, où le fichier iCalendar ne s'ouvre pas toujours dans l'agenda.
     * @param array<string, mixed> $e @return array{google: string, outlook: string}
     */
    public static function liens(array $e): array
    {
        $debut = self::utc((string) $e['starts_at']) ?? '';
        $fin = self::utc((string) ($e['ends_at'] ?: $e['starts_at'])) ?? $debut;
        $iso = static fn (string $u): string => $u === '' ? '' : substr($u, 0, 4) . '-' . substr($u, 4, 2) . '-' . substr($u, 6, 2) . 'T' . substr($u, 9, 2) . ':' . substr($u, 11, 2) . ':00Z';
        $page = Langue::absolue('/agenda/' . $e['slug'] . '/');
        $texte = trim((string) ($e['description'] ?? '') . "\n" . $page);
        return [
            'google' => 'https://calendar.google.com/calendar/render?' . http_build_query(['action' => 'TEMPLATE', 'text' => (string) $e['title'], 'dates' => $debut . '/' . $fin, 'details' => $texte, 'location' => (string) ($e['address'] ?? '')], '', '&', PHP_QUERY_RFC3986),
            'outlook' => 'https://outlook.live.com/calendar/0/deeplink/compose?' . http_build_query(['path' => '/calendar/action/compose', 'rru' => 'addevent', 'subject' => (string) $e['title'], 'startdt' => $iso($debut), 'enddt' => $iso($fin), 'body' => $texte, 'location' => (string) ($e['address'] ?? '')], '', '&', PHP_QUERY_RFC3986),
        ];
    }

    /**
     * Abonnement au calendrier complet, par application : Apple (webcal://), Google Agenda (cid), Outlook (addfromweb).
     * Les trois sont de vrais abonnements : l'agenda relit le calendrier et ajoute seul les nouveaux rendez-vous.
     * @return array{apple: string, google: string, outlook: string}
     */
    public static function abonnements(): array
    {
        $flux = Langue::absolue('/agenda.ics');
        if (preg_match('#^https?://#', $flux) !== 1) {
            // Poste de travail sans APP_URL : adresse du serveur local seulement (jamais un en-tête Host venu d'ailleurs).
            $hote = (string) ($_SERVER['HTTP_HOST'] ?? '');
            $flux = preg_match('#^(localhost|127\.0\.0\.1)(:\d+)?$#', $hote) === 1 ? 'http://' . $hote . '/agenda.ics' : '';
        }
        if ($flux === '') {
            return ['apple' => '', 'google' => '', 'outlook' => ''];
        }
        $webcal = (string) preg_replace('#^https?://#', 'webcal://', $flux);
        return [
            'apple' => $webcal,
            'google' => 'https://calendar.google.com/calendar/r?cid=' . rawurlencode($webcal),
            'outlook' => 'https://outlook.live.com/calendar/0/addfromweb?' . http_build_query(['url' => $flux, 'name' => (string) Site::get('nom_court', 'Agenda')], '', '&', PHP_QUERY_RFC3986),
        ];
    }

    /** @param list<array<string, mixed>> $evenements */
    private static function envoyer(array $evenements, string $fichier, bool $piece): void
    {
        $nom = (string) Site::get('nom', 'Agenda');
        $hote = (string) (parse_url(Site::url(), PHP_URL_HOST) ?: 'localhost');
        $maintenant = gmdate('Ymd\THis\Z');
        $lignes = ['BEGIN:VCALENDAR', 'VERSION:2.0', 'PRODID:-//' . self::texte($nom) . '//Agenda//FR', 'CALSCALE:GREGORIAN', 'METHOD:PUBLISH', 'X-WR-CALNAME:' . self::texte($nom), 'X-WR-TIMEZONE:Europe/Paris'];
        foreach ($evenements as $e) {
            $debut = self::utc((string) $e['starts_at']);
            $fin = self::utc((string) ($e['ends_at'] ?: $e['starts_at']));
            if ($debut === null) {
                continue;
            }
            $lignes[] = 'BEGIN:VEVENT';
            $lignes[] = 'UID:evenement-' . (int) $e['id'] . '@' . $hote;
            $lignes[] = 'DTSTAMP:' . $maintenant;
            $lignes[] = 'DTSTART:' . $debut;
            $lignes[] = 'DTEND:' . ($fin !== null && $fin > $debut ? $fin : $debut);
            $lignes[] = 'SUMMARY:' . self::texte((string) $e['title']);
            if (trim((string) $e['address']) !== '') {
                $lignes[] = 'LOCATION:' . self::texte((string) $e['address']);
            }
            if (trim((string) $e['description']) !== '') {
                $lignes[] = 'DESCRIPTION:' . self::texte((string) $e['description']);
            }
            $lignes[] = 'URL:' . Langue::absolue('/agenda/' . $e['slug'] . '/');
            $lignes[] = 'END:VEVENT';
        }
        $lignes[] = 'END:VCALENDAR';
        header('Content-Type: text/calendar; charset=utf-8');
        header('Content-Disposition: ' . ($piece ? 'attachment' : 'inline') . '; filename="' . $fichier . '"');
        header('X-Robots-Tag: noindex');
        header('Cache-Control: public, max-age=3600');
        echo implode("\r\n", array_map(self::replier(...), $lignes)) . "\r\n";
    }

    /** « 2026-12-07 18:00 » (heure de Paris) en « 20261207T170000Z ». */
    private static function utc(string $local): ?string
    {
        try {
            $d = new \DateTimeImmutable($local, new \DateTimeZone('Europe/Paris'));
        } catch (\Exception) {
            return null;
        }
        return $d->setTimezone(new \DateTimeZone('UTC'))->format('Ymd\THis\Z');
    }

    /** Échappement RFC 5545 : barre oblique inverse, point-virgule, virgule, retours à la ligne. */
    private static function texte(string $valeur): string
    {
        $valeur = str_replace(["\r\n", "\r"], "\n", trim($valeur));
        return strtr($valeur, ['\\' => '\\\\', ';' => '\\;', ',' => '\\,', "\n" => '\\n']);
    }

    /** Ligne repliée à 75 octets au plus, sans couper un caractère UTF-8 ; suite précédée d'une espace. */
    private static function replier(string $ligne): string
    {
        $sortie = '';
        $courante = '';
        foreach (preg_split('//u', $ligne, -1, PREG_SPLIT_NO_EMPTY) ?: [] as $c) {
            $limite = $sortie === '' ? 75 : 74;
            if (strlen($courante) + strlen($c) > $limite) {
                $sortie .= ($sortie === '' ? '' : "\r\n ") . $courante;
                $courante = '';
            }
            $courante .= $c;
        }
        return $sortie === '' ? $courante : $sortie . "\r\n " . $courante;
    }
}
