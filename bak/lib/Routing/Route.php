<?php

declare(strict_types=1);

namespace CapsuleLib\Routing;

final class Route
{
    public function __construct(
        public string $method,
        public string $pathPattern,              // ex: /dashboard/articles/edit/{id:\d+}
        /** @var callable(array):void */
        public $handler,
        /** @var array<int, callable> */
        public array $middlewares = [],
        public ?string $name = null
    ) {}

    /** Convertit {name:regex} → (?P<name>regex) */
    public function toRegex(): string
    {
        $regex = preg_replace_callback(
            '/\{([a-zA-Z_][a-zA-Z0-9_]*)\:([^}]+)\}/',
            fn($m) => '(?P<' . $m[1] . '>' . $m[2] . ')',
            $this->pathPattern
        );

        // params sans contrainte : {slug} → (?P<slug>[^/]+)
        $regex = preg_replace(
            '/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/',
            '(?P<$1>[^/]+)',
            $regex
        );

        return '#^' . $regex . '$#';
    }
}
