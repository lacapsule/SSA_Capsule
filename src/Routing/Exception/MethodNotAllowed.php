<?php

declare(strict_types=1);

namespace Capsule\Routing\Exception;

/**
 * Exceptions dédiées au routing (mappées par l’ErrorBoundary).
 */
final class MethodNotAllowed extends \RuntimeException
{
    /** @param list<string> $allowed */
    public function __construct(public readonly array $allowed)
    {
        parent::__construct('Method Not Allowed');
    }
}
