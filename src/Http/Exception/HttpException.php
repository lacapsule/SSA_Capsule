<?php

declare(strict_types=1);

namespace Capsule\Http\Exception;

/**
 * Exception HTTP applicative (pour signaler 4xx/5xx).
 *
 * @psalm-immutable
 * @phpstan-immutable
 *
 * @param array<string,list<string>> $headers
 */
final class HttpException extends \RuntimeException
{
    /**
     * @param array<string,list<string>> $headers
     */
    public function __construct(
        public readonly int $status,
        string $message = '',
        public readonly array $headers = []
    ) {
        if ($status < 400 || $status > 599) {
            throw new \InvalidArgumentException("HttpException status must be 4xx or 5xx, got {$status}");
        }
        parent::__construct($message, $status);
    }
}
