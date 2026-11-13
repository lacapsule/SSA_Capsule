<?php
declare(strict_types=1);

namespace App\Site;

use App\Langue\Langue;
use IntlDateFormatter;

/** Dates affichées dans la langue de la page (ICU : « vendredi 13 novembre 2026 », « Gwener 13 Du 2026 »). */
final class Dates
{
    private static function format(string $date, string $motif): string
    {
        $f = new IntlDateFormatter(Langue::locale(), IntlDateFormatter::NONE, IntlDateFormatter::NONE, 'Europe/Paris', null, $motif);
        return (string) $f->format(new \DateTimeImmutable($date, new \DateTimeZone('Europe/Paris')));
    }

    public static function longue(string $date): string
    {
        return self::format($date, 'EEEE d MMMM y');
    }

    /** Pastille : jour en chiffres, mois abrégé. @return array{jour: string, mois: string} */
    public static function pastille(string $date): array
    {
        return ['jour' => self::format($date, 'd'), 'mois' => rtrim(self::format($date, 'MMM'), '.')];
    }

    /** Mois et année, initiale en capitale (« Novembre 2026 »). */
    public static function mois(string $date): string
    {
        $m = self::format($date, 'LLLL y');
        return mb_convert_case(mb_substr($m, 0, 1), MB_CASE_UPPER) . mb_substr($m, 1);
    }

    public static function heure(string $date): string
    {
        return self::format($date, 'HH:mm');
    }

    /** Valeur de l'attribut datetime. */
    public static function iso(string $date): string
    {
        return (new \DateTimeImmutable($date, new \DateTimeZone('Europe/Paris')))->format(str_contains($date, ':') ? 'Y-m-d\TH:i' : 'Y-m-d');
    }
}
