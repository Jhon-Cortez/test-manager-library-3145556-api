<?php

declare(strict_types=1);

namespace App\Application\Ports\Outbound;

use App\Application\Ports\Outbound\Support\Page;
use App\Domain\Book;

interface BookRepository
{
    public function findById(string $id): ?Book;

    /** @param string[] $ids @return Book[] */
    public function findByIds(array $ids): array;

    public function add(Book $book): void;

    public function save(Book $book): void;

    public function existsByIsbn(string $isbn, ?string $exceptBookId = null): bool;

    public function search(?string $query, ?string $categoryId, int $page, int $size): Page;
}
