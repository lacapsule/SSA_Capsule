<?php

declare(strict_types=1);

namespace Capsule\Routing\Attribute;

#[\Attribute(\Attribute::TARGET_CLASS)]
final class RoutePrefix
{
    public function __construct(public readonly string $prefix)
    {
    }
}
