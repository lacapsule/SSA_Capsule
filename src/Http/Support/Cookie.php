<?php

declare(strict_types=1);

namespace Capsule\Http\Support;

final class Cookie
{
    public function __construct(
        public readonly string $name,
        public readonly string $value,
        public readonly ?int $expires = null, // timestamp UTC
        public readonly ?int $maxAge = null,
        public readonly string $path = '/',
        public readonly ?string $domain = null,
        public readonly bool $secure = true,
        public readonly bool $httpOnly = true,
        public readonly string $sameSite = 'Lax' // Lax|Strict|None
    ) {
    }

    public function toHeader(): string
    {
        $p = [rawurlencode($this->name) . '=' . rawurlencode($this->value)];
        if ($this->expires) {
            $p[] = 'Expires=' . gmdate('D, d M Y H:i:s T', $this->expires);
        }
        if ($this->maxAge) {
            $p[] = 'Max-Age=' . $this->maxAge;
        }
        if ($this->domain) {
            $p[] = 'Domain=' . $this->domain;
        }
        if ($this->path) {
            $p[] = 'Path=' . $this->path;
        }
        if ($this->secure) {
            $p[] = 'Secure';
        }
        if ($this->httpOnly) {
            $p[] = 'HttpOnly';
        }
        if (in_array($this->sameSite, ['Lax','Strict','None'], true)) {
            $p[] = 'SameSite=' . $this->sameSite;
        }

        return implode('; ', $p);
    }
}
