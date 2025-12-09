<?php

declare(strict_types=1);

namespace CapsuleLib\Http;

final class RequestUtils
{
    public static function isPost(): bool
    {
        return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
    }

    public static function intFromParam(string|int|array $param): int
    {
        if (is_array($param)) {
            return (int)($param['id'] ?? 0);
        }
        return (int)$param;
    }

    /** Si pas POST, redirect PRG vers $to (303) */
    public static function ensurePostOrRedirect(string $to): void
    {
        if (!self::isPost()) {
            Redirect::to($to, 303);
        }
    }
}
