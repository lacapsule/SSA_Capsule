<?php
declare(strict_types=1);

namespace App\Lieux;

/**
 * Quartier d'un point (rues et cours d'eau dans un rayon d'un kilomètre), lu auprès de l'IGN (Géoplateforme, BD TOPO,
 * Licence Ouverte) depuis le serveur, à l'enregistrement d'un événement : même calcul et même format que la partie 2 de
 * scripts/carte-ign.mjs (config/carte-quartiers.json), en mètres autour du point. Aucun appel depuis les pages publiques.
 */
final class Quartier
{
    public const RAYON = 1000;
    private const WFS = 'https://data.geopf.fr/wfs/ows';

    /** @return ?array{lat: float, lon: float, routes: array{grandes: string, moyennes: string, petites: string}, eau: string} null si l'IGN ne répond pas */
    public static function recuperer(float $lat, float $lon): ?array
    {
        $mx = cos(deg2rad($lat)) * 111320;
        $my = 110540;
        $marge = self::RAYON * 1.6;
        $bbox = [$lon - $marge / $mx, $lat - $marge / $my, $lon + $marge / $mx, $lat + $marge / $my];
        $routes = self::entites('BDTOPO_V3:troncon_de_route', $bbox, "importance IN ('1','2','3','4','5') AND fictif = false", 'geometrie,importance');
        $eau = self::entites('BDTOPO_V3:troncon_hydrographique', $bbox, "persistance = 'Permanent'", 'geometrie');
        if ($routes === null || $eau === null) {
            return null;
        }
        $projeter = static fn (array $c): array => [((float) $c[0] - $lon) * $mx, ($lat - (float) $c[1]) * $my];
        $par = static fn (array $valeurs): array => array_values(array_filter($routes, static fn (array $f): bool => in_array((string) ($f['properties']['importance'] ?? ''), $valeurs, true)));
        return [
            'lat' => $lat, 'lon' => $lon,
            'routes' => ['grandes' => self::chemin($par(['1', '2', '3']), $projeter), 'moyennes' => self::chemin($par(['4']), $projeter), 'petites' => self::chemin($par(['5']), $projeter)],
            'eau' => self::chemin($eau, $projeter),
        ];
    }

    /** @param array{0: float, 1: float, 2: float, 3: float} $bbox lonMin, latMin, lonMax, latMax @return ?list<array<string, mixed>> */
    private static function entites(string $type, array $bbox, string $filtre, string $champs): ?array
    {
        $toutes = [];
        for ($debut = 0; $debut < 50000; $debut += 5000) {
            $q = http_build_query([
                'SERVICE' => 'WFS', 'VERSION' => '2.0.0', 'REQUEST' => 'GetFeature', 'TYPENAMES' => $type, 'OUTPUTFORMAT' => 'application/json',
                'COUNT' => '5000', 'STARTINDEX' => (string) $debut, 'SRSNAME' => 'EPSG:4326', 'PROPERTYNAME' => $champs,
                // CQL : longitude d'abord.
                'CQL_FILTER' => sprintf("%s AND BBOX(geometrie,%.6F,%.6F,%.6F,%.6F,'EPSG:4326')", $filtre, ...$bbox),
            ]);
            $json = self::lire(self::WFS . '?' . $q);
            $data = $json !== null ? json_decode($json, true) : null;
            if (!is_array($data) || !is_array($data['features'] ?? null)) {
                return null;
            }
            array_push($toutes, ...$data['features']);
            if (count($data['features']) < 5000) {
                return $toutes;
            }
        }
        return $toutes;
    }

    /** Réponse du service, ou null (réseau coupé, délai dépassé) : l'avertissement de PHP n'interrompt pas l'enregistrement. */
    private static function lire(string $url): ?string
    {
        $contexte = stream_context_create(['http' => ['timeout' => 8, 'ignore_errors' => true, 'header' => "Accept: application/json\r\n"]]);
        set_error_handler(static fn (): bool => true);
        try {
            $json = file_get_contents($url, false, $contexte);
        } finally {
            restore_error_handler();
        }
        return is_string($json) && $json !== '' ? $json : null;
    }

    /** @param list<array<string, mixed>> $entites @param callable(array): array{0: float, 1: float} $projeter */
    private static function chemin(array $entites, callable $projeter): string
    {
        $morceaux = [];
        foreach ($entites as $f) {
            $g = $f['geometry'] ?? null;
            $lignes = match ($g['type'] ?? '') { 'LineString' => [$g['coordinates']], 'MultiLineString' => $g['coordinates'], default => [] };
            foreach ($lignes as $l) {
                $p = self::simplifier(array_map($projeter, $l), 4.0);
                if (count($p) < 2) {
                    continue;
                }
                $morceaux[] = 'M' . implode('L', array_map(static fn (array $pt): string => sprintf('%.0F %.0F', $pt[0], $pt[1]), $p));
            }
        }
        return implode('', $morceaux);
    }

    /** Douglas-Peucker sur une ligne projetée. @param list<array{0: float, 1: float}> $points @return list<array{0: float, 1: float}> */
    private static function simplifier(array $points, float $tol): array
    {
        $n = count($points);
        if ($n < 3) {
            return $points;
        }
        $garder = array_fill(0, $n, false);
        $garder[0] = $garder[$n - 1] = true;
        $pile = [[0, $n - 1]];
        while ($pile !== []) {
            [$a, $b] = array_pop($pile);
            [$ax, $ay] = $points[$a];
            [$bx, $by] = $points[$b];
            $dx = $bx - $ax;
            $dy = $by - $ay;
            $l2 = ($dx * $dx + $dy * $dy) ?: 1.0;
            $max = 0.0;
            $idx = -1;
            for ($i = $a + 1; $i < $b; $i++) {
                $t = max(0.0, min(1.0, (($points[$i][0] - $ax) * $dx + ($points[$i][1] - $ay) * $dy) / $l2));
                $d = hypot($points[$i][0] - ($ax + $t * $dx), $points[$i][1] - ($ay + $t * $dy));
                if ($d > $max) {
                    $max = $d;
                    $idx = $i;
                }
            }
            if ($max > $tol && $idx > 0) {
                $garder[$idx] = true;
                $pile[] = [$a, $idx];
                $pile[] = [$idx, $b];
            }
        }
        return array_values(array_filter($points, static fn (int $i): bool => $garder[$i], ARRAY_FILTER_USE_KEY));
    }
}
