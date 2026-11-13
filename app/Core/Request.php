<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Adresse IP du visiteur et HTTPS, sans croire les en-têtes que n'importe qui peut envoyer.
 * Par défaut : REMOTE_ADDR et la variable HTTPS du serveur, rien d'autre.
 * Derrière un mandataire inverse (répartiteur, CDN) : déclarer ses adresses dans config/app.php, security.trusted_proxies
 * (IP ou plages CIDR). X-Forwarded-For et X-Forwarded-Proto ne sont lus que si la connexion vient de l'un d'eux.
 */
final class Request
{
    public static function ip(): string
    {
        $remote = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
        if ($remote === '') {
            return PHP_SAPI === 'cli' ? 'cli' : '0.0.0.0';
        }
        if (!self::fromTrustedProxy()) {
            return $remote;
        }
        // Lecture de droite à gauche : la première adresse qui n'est pas un mandataire de confiance est celle du visiteur.
        $chain = array_reverse(array_map('trim', explode(',', (string) ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? ''))));
        foreach ($chain as $ip) {
            if (filter_var($ip, FILTER_VALIDATE_IP) === false) {
                break;
            }
            if (!self::trusted($ip)) {
                return $ip;
            }
        }
        return $remote;
    }

    public static function isHttps(): bool
    {
        if (($_SERVER['HTTPS'] ?? '') !== '' && strtolower((string) $_SERVER['HTTPS']) !== 'off') {
            return true;
        }
        return self::fromTrustedProxy() && strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https';
    }

    /** Hôte demandé, en minuscules, avec le port s'il est donné ; chaîne vide s'il est malformé. */
    public static function host(): string
    {
        $host = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));
        return preg_match('/^(\[[0-9a-f:.]+\]|[a-z0-9.-]+)(:\d{1,5})?$/', $host) === 1 ? $host : '';
    }

    private static function fromTrustedProxy(): bool
    {
        return self::trusted((string) ($_SERVER['REMOTE_ADDR'] ?? ''));
    }

    private static function trusted(string $ip): bool
    {
        foreach ((array) config('security.trusted_proxies', []) as $range) {
            if (self::inRange($ip, (string) $range)) {
                return true;
            }
        }
        return false;
    }

    private static function inRange(string $ip, string $range): bool
    {
        [$subnet, $bits] = array_pad(explode('/', $range, 2), 2, null);
        $ipBin = @inet_pton($ip);
        $subnetBin = @inet_pton((string) $subnet);
        if ($ipBin === false || $subnetBin === false || strlen($ipBin) !== strlen($subnetBin)) {
            return false;
        }
        $bits = $bits === null ? strlen($ipBin) * 8 : (int) $bits;
        $bytes = intdiv($bits, 8);
        if (strncmp($ipBin, $subnetBin, $bytes) !== 0) {
            return false;
        }
        $rest = $bits % 8;
        if ($rest === 0) {
            return true;
        }
        $mask = (0xff << (8 - $rest)) & 0xff;
        return (ord($ipBin[$bytes]) & $mask) === (ord($subnetBin[$bytes]) & $mask);
    }
}
