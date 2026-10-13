<?php

declare(strict_types=1);

namespace Tests\Fakes;

use App\Application\Ports\Outbound\CategoryReader;

final class InMemoryCategoryReader implements CategoryReader
{
    /** @param array<string, string> $categories id => name */
    public function __construct(private array $categories = [])
    {
    }

    public function all(): array
    {
        $rows = [];
        foreach ($this->categories as $id => $name) {
            $rows[] = ['id' => $id, 'name' => $name];
        }
        usort($rows, static fn (array $a, array $b): int => strcmp($a['name'], $b['name']));

        return $rows;
    }

    public function nameOf(string $categoryId): ?string
    {
        return $this->categories[$categoryId] ?? null;
    }
}
