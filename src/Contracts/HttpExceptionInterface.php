<?php

declare(strict_types=1);

namespace Capsule\Contracts;

interface HttpExceptionInterface
{
    public function getStatusCode(): int;
    /** @return array<string,string> */
    public function getHeaders(): array;
    /** @return array<string,mixed> Payload “safe” pour le client */
    public function getPayload(): array;
    public function getTitle(): ?string;
}
