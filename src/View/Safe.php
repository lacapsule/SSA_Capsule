<?php

declare(strict_types=1);

namespace Capsule\View;

final class Safe
{
    public static function imageUrl(string $raw, string $fallback = '/assets/img/logoSSA.png'): string
    {
        $raw = trim($raw);
        if ($raw === '') {
            return $fallback;
        }
        if (str_starts_with($raw, '/assets/')) {
            return $raw;
        }
        if (filter_var($raw, FILTER_VALIDATE_URL) && preg_match('#^https?://#i', $raw)) {
            return $raw;
        }

        return $fallback;
    }
}
