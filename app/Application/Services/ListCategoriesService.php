<?php

declare(strict_types=1);

namespace App\Application\Services;

use App\Application\Ports\Outbound\CategoryReader;

final class ListCategoriesService
{
    public function __construct(private readonly CategoryReader $categories)
    {
    }

    /** @return array<int, array{id: string, name: string}> */
    public function list(): array
    {
        return $this->categories->all();
    }
}
