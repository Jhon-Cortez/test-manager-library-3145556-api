<?php

declare(strict_types=1);

namespace App\Application\Ports\Outbound;

interface CategoryReader
{
    /** @return array<int, array{id: string, name: string}> */
    public function all(): array;

    public function nameOf(string $categoryId): ?string;
}
