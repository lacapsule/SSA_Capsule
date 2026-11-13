<?php
declare(strict_types=1);

namespace App\Core;

/**
 * En-têtes de sécurité de chaque réponse dynamique (config/app.php, section security).
 * CSP stricte : tout vient du site, aucun script ni style en ligne, aucune page ne peut être encadrée.
 */
final class Headers
{
    /** Directives de base ; config security.csp y ajoute des sources, jamais 'unsafe-inline'. */
    private const CSP = [
        'default-src' => ["'self'"],
        'img-src' => ["'self'", 'data:', 'blob:'],
        'style-src' => ["'self'"],
        'script-src' => ["'self'"],
        'font-src' => ["'self'"],
        'connect-src' => ["'self'"],
        'frame-src' => ["'none'"],
        'object-src' => ["'none'"],
        'base-uri' => ["'self'"],
        'form-action' => ["'self'"],
        'frame-ancestors' => ["'none'"],
    ];

    public static function send(string $path): void
    {
        header('Content-Security-Policy: ' . self::csp());
        header('X-Content-Type-Options: nosniff');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: ' . (string) config('security.permissions_policy', 'camera=(), microphone=(), geolocation=()'));
        header('Cross-Origin-Opener-Policy: same-origin');
        header('X-Frame-Options: DENY');
        header_remove('X-Powered-By');

        // HSTS seulement sur le domaine public servi en HTTPS : jamais sur localhost, une préproduction ou une IP.
        $canonical = strtolower((string) config('security.canonical_host', ''));
        if ($canonical !== '' && Request::isHttps() && Request::host() === $canonical) {
            header('Strict-Transport-Security: max-age=' . (int) config('security.hsts_max_age', 31536000)
                . (config('security.hsts_subdomains', false) ? '; includeSubDomains' : ''));
        }

        if (self::isPrivate($path)) {
            header('X-Robots-Tag: noindex, nofollow');
            header('Cache-Control: no-store');
        }
    }

    public static function csp(): string
    {
        $directives = self::CSP;
        foreach ((array) config('security.csp', []) as $name => $sources) {
            $sources = array_values(array_filter((array) $sources, static fn ($s): bool => is_string($s) && $s !== '' && !str_contains($s, 'unsafe-')));
            if (!isset($directives[$name]) || $sources === []) {
                continue;
            }
            $base = $directives[$name] === ["'none'"] ? [] : $directives[$name];
            $directives[$name] = array_values(array_unique([...$base, ...$sources]));
        }
        $out = [];
        foreach ($directives as $name => $sources) {
            $out[] = $name . ' ' . implode(' ', $sources);
        }
        return implode('; ', $out);
    }

    private static function isPrivate(string $path): bool
    {
        foreach ((array) config('security.private_prefixes', []) as $prefix) {
            $prefix = rtrim((string) $prefix, '/');
            if ($prefix !== '' && ($path === $prefix || str_starts_with($path, $prefix . '/') || str_starts_with($path, $prefix . '.'))) {
                return true;
            }
        }
        return false;
    }
}
