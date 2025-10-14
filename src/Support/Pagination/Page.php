<?php

declare(strict_types=1);

namespace Capsule\Support\Pagination;

final class Page
{
    public function __construct(
        public readonly int $page,
        public readonly int $limit,
        public readonly int $total
    ) {
    }
    public function offset(): int
    {
        return ($this->page - 1) * $this->limit;
    }
    public function pages(): int
    {
        return (int)max(1, ceil($this->total / max(1, $this->limit)));
    }
    public function hasNext(): bool
    {
        return $this->page < $this->pages();
    }
    public function hasPrev(): bool
    {
        return $this->page > 1;
    }
}
