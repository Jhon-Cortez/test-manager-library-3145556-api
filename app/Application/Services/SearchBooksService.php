<?php

declare(strict_types=1);

namespace App\Application\Services;

use App\Application\Ports\Outbound\BookRepository;
use App\Application\Ports\Outbound\Support\Page;

final class SearchBooksService
{
    public const MAX_PAGE_SIZE = 50;

    public function __construct(private readonly BookRepository $books)
    {
    }

    public function search(?string $query, ?string $categoryId, int $page = 1, int $size = 20): Page
    {
        $page = max(1, $page);
        $size = min(max(1, $size), self::MAX_PAGE_SIZE);

        return $this->books->search($query, $categoryId, $page, $size);
    }
}
