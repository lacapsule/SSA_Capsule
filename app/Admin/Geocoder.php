<?php
declare(strict_types=1);

namespace App\Admin;

/**
 * Géocodage facultatif des adresses, appelé seulement depuis l'admin (jamais depuis les pages publiques).
 * Par défaut : Géoplateforme de l'IGN (service public, France, sans clé, format GeoJSON « features »).
 * Autre service au même format : GEOCODER_URL dans .env. Centre de recherche : clé « centre » de la ressource.
 */
final class Geocoder
{
    private const DEFAULT_URL = 'https://data.geopf.fr/geocodage/search';

    /**
     * Suggestions pour la saisie (liste déroulante de l'admin).
     * @param array{0: float, 1: float}|null $centre latitude, longitude
     * @return list<array{label: string, context: string, name: string, postcode: string, city: string}>
     */
    public static function suggest(string $q, ?array $centre = null): array
    {
        $out = [];
        foreach (self::query($q, 6, $centre) as $f) {
            if (isset($f['properties']['label'])) {
                $pr = $f['properties'];
                // name, postcode, city : rue (ou lieu-dit), code postal et commune, pour remplir les champs séparés.
                $out[] = ['label' => (string) $pr['label'], 'context' => (string) ($pr['context'] ?? ''), 'name' => (string) ($pr['name'] ?? ''), 'postcode' => (string) ($pr['postcode'] ?? ''), 'city' => (string) ($pr['city'] ?? '')];
            }
        }
        return $out;
    }

    /**
     * Position d'une adresse, ou null si elle est introuvable ou trop incertaine.
     * @param array{0: float, 1: float}|null $centre
     * @return array{lat: float, lon: float, label: string}|null
     */
    public static function locate(string $address, ?array $centre = null): ?array
    {
        $hit = self::query($address, 1, $centre)[0] ?? null;
        if (!is_array($hit) || (float) ($hit['properties']['score'] ?? 0) < 0.5 || !isset($hit['geometry']['coordinates'][1])) {
            return null;
        }
        [$lon, $lat] = $hit['geometry']['coordinates'];
        return ['lat' => round((float) $lat, 6), 'lon' => round((float) $lon, 6), 'label' => (string) ($hit['properties']['label'] ?? $address)];
    }

    /** @return list<array<string, mixed>> */
    private static function query(string $q, int $limit, ?array $centre): array
    {
        $q = trim($q);
        $len = (int) preg_match_all('/./su', $q);
        if ($len < 3 || $len > 200) {
            return [];
        }
        $params = ['q' => $q, 'limit' => $limit];
        if ($centre !== null) {
            $params += ['lat' => (float) $centre[0], 'lon' => (float) $centre[1]];
        }
        $url = (string) ($_ENV['GEOCODER_URL'] ?? self::DEFAULT_URL);
        $context = stream_context_create(['http' => ['timeout' => 4, 'ignore_errors' => true, 'header' => "Accept: application/json\r\n"]]);
        $json = @file_get_contents($url . '?' . http_build_query($params), false, $context);
        $data = is_string($json) ? json_decode($json, true) : null;
        return is_array($data['features'] ?? null) ? array_values($data['features']) : [];
    }
}
