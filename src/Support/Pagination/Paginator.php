<?php

declare(strict_types=1);

namespace Capsule\Support\Pagination;

final class Paginator
{
    public static function fromGlobals(int $defaultLimit = 12, int $maxLimit = 100): Page
    {
        $p = max(1, (int)($_GET['page'] ?? 1));
        $l = min($maxLimit, max(1, (int)($_GET['limit'] ?? $defaultLimit)));

        // total inconnu ici → 0 ; l’app peut refaire un Page avec total réel.
        return new Page($p, $l, 0);
    }
}
