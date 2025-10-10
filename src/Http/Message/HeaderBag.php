<?php

declare(strict_types=1);

namespace Capsule\Http\Message;

final class HeaderBag
{
    /** @var array<string,list<string>> */
    private array $headers = [];

    /**
     * @param string|list<string> $values
     */
    public function set(string $name, string|array $values): void
    {
        $n = self::normalizeName($name);
        $vals = is_array($values) ? $values : [$values];
        $clean = [];
        foreach ($vals as $v) {
            $v = self::sanitizeValue($v);
            if ($v !== '') {
                $clean[] = $v;
            }
        }
        $this->headers[$n] = $clean;
    }

    public function add(string $name, string $value): void
    {
        $n = self::normalizeName($name);
        $v = self::sanitizeValue($value);
        $this->headers[$n] = $this->headers[$n] ?? [];
        $this->headers[$n][] = $v;
    }

    public function get(string $name, ?string $default = null): ?string
    {
        $n = self::normalizeName($name);

        return $this->headers[$n][0] ?? $default;
    }

    /** @return list<string> */
    public function getAll(string $name): array
    {
        $n = self::normalizeName($name);

        return $this->headers[$n] ?? [];
    }

    /** @return array<string,list<string>> */
    public function all(): array
    {
        return $this->headers;
    }

    public function has(string $name): bool
    {
        return isset($this->headers[self::normalizeName($name)]);
    }

    public function remove(string $name): void
    {
        unset($this->headers[self::normalizeName($name)]);
    }

    private static function normalizeName(string $name): string
    {
        $name = trim($name);
        if (!preg_match("/^[!#$%&'*+.^_`|~0-9A-Za-z-]+$/", $name)) {
            throw new \InvalidArgumentException('Invalid header name');
        }

        return str_replace(' ', '-', ucwords(strtolower(str_replace('-', ' ', $name))));
    }

    private static function sanitizeValue(string $v): string
    {
        return preg_replace('/[\x00-\x1F\x7F]/', '', trim($v)) ?? '';
    }
}
