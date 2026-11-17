<?php
declare(strict_types=1);

namespace App\Lieux;

/**
 * Position exacte d'un lieu d'événement ou du contact, relevée auprès de l'IGN (config/lieux-evenements.tsv) et détails
 * de son quartier (config/carte-quartiers.json, généré par scripts/carte-ign.mjs). Une adresse absente n'a pas de
 * position : la carte de sa commune est montrée à la place, jamais un point deviné.
 */
final class Positions
{
    /** @var ?array<string, array{lat: float, lon: float, precision: string, cle: string, ign: string}> */
    private static ?array $table = null;

    /** @return ?array{lat: float, lon: float, precision: string, cle: string, ign: string} */
    public static function pour(string $adresse): ?array
    {
        return self::table()[trim($adresse)] ?? null;
    }

    /** @return ?array{lat: float, lon: float, precision: string, cle: string, ign: string} */
    public static function parCle(string $cle): ?array
    {
        foreach (self::table() as $p) {
            if ($p['cle'] === $cle) {
                return $p;
            }
        }
        return null;
    }

    /** @return ?array<string, mixed> routes et eau du quartier, en mètres autour du point */
    public static function quartier(string $cle): ?array
    {
        static $quartiers = null;
        if ($quartiers === null) {
            $f = ROOT . '/config/carte-quartiers.json';
            $quartiers = is_file($f) ? (json_decode((string) file_get_contents($f), true) ?: []) : [];
        }
        return $quartiers['points'][$cle] ?? null;
    }

    /** @return array<string, array{lat: float, lon: float, precision: string, cle: string, ign: string}> */
    private static function table(): array
    {
        if (self::$table !== null) {
            return self::$table;
        }
        self::$table = [];
        $f = ROOT . '/config/lieux-evenements.tsv';
        foreach (is_file($f) ? (file($f, FILE_IGNORE_NEW_LINES) ?: []) : [] as $ligne) {
            if ($ligne === '' || str_starts_with($ligne, '#')) {
                continue;
            }
            $c = explode("\t", $ligne);
            if (count($c) >= 6) {
                self::$table[$c[0]] = ['lat' => (float) $c[1], 'lon' => (float) $c[2], 'precision' => $c[3], 'cle' => $c[4], 'ign' => $c[5]];
            }
        }
        return self::$table;
    }
}
