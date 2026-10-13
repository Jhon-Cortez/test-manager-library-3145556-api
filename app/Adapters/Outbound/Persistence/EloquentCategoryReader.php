<?php

declare(strict_types=1);

namespace App\Adapters\Outbound\Persistence;

use App\Adapters\Outbound\Persistence\Models\CategoryModel;
use App\Application\Ports\Outbound\CategoryReader;

final class EloquentCategoryReader implements CategoryReader
{
    public function all(): array
    {
        return CategoryModel::query()->orderBy('name')->get()
            ->map(fn (CategoryModel $row): array => [
                'id' => (string) $row->id,
                'name' => (string) $row->name,
            ])
            ->all();
    }

    public function nameOf(string $categoryId): ?string
    {
        $row = CategoryModel::query()->find($categoryId);

        return $row === null ? null : (string) $row->name;
    }
}
