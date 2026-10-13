<?php

declare(strict_types=1);

namespace App\Application\Ports\Outbound\Support;

final class Page
{
    /** @param array<int, mixed> $items */
    public function __construct(
        private readonly array $items,
        private readonly int $total,
        private readonly int $page,
        private readonly int $size,
    ) {
    }

    /** @return array<int, mixed> */
    public function items(): array
    {
        return $this->items;
    }

    public function total(): int
    {
        return $this->total;
    }

    public function page(): int
    {
        return $this->page;
    }

    public function size(): int
    {
        return $this->size;
    }
}
