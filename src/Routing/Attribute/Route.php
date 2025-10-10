<?php

declare(strict_types=1);

namespace Capsule\Routing\Attribute;

#[\Attribute(\Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
final class Route
{
    /** 
     * @param list<string> $methods 
     * @param list<class-string> $middlewares 
     */
    public function __construct(
        public readonly string $path,
        public readonly array $methods = ['GET'],
        public readonly ?string $name = null,
        public readonly array $middlewares = [],
    ) {
    }
}
