<?php

declare(strict_types=1);

namespace Tests\Fakes;

use App\Application\Ports\Outbound\BookRepository;
use App\Application\Ports\Outbound\Support\Page;
use App\Domain\Book;

final class InMemoryBookRepository implements BookRepository
{
    /** @var array<string, Book> */
    private array $books = [];

    public function findById(string $id): ?Book
    {
        return $this->books[$id] ?? null;
    }

    public function findByIds(array $ids): array
    {
        $found = [];
        foreach ($ids as $id) {
            if (isset($this->books[$id])) {
                $found[] = $this->books[$id];
            }
        }

        return $found;
    }

    public function add(Book $book): void
    {
        $this->books[$book->id()] = $book;
    }

    public function save(Book $book): void
    {
        $this->books[$book->id()] = $book;
    }

    public function existsByIsbn(string $isbn, ?string $exceptBookId = null): bool
    {
        foreach ($this->books as $book) {
            if ($book->isDeleted() || $book->isbn()->value() !== $isbn) {
                continue;
            }
            if ($exceptBookId !== null && $book->id() === $exceptBookId) {
                continue;
            }

            return true;
        }

        return false;
    }

    public function search(?string $query, ?string $categoryId, int $page, int $size): Page
    {
        $filtered = [];
        foreach ($this->books as $book) {
            if ($book->isDeleted()) {
                continue;
            }
            if ($categoryId !== null && $book->categoryId() !== $categoryId) {
                continue;
            }
            if ($query !== null && $query !== '') {
                $needle = mb_strtolower($query);
                $inTitle = str_contains(mb_strtolower($book->title()), $needle);
                $inAuthor = str_contains(mb_strtolower($book->author()), $needle);
                if (! $inTitle && ! $inAuthor) {
                    continue;
                }
            }
            $filtered[] = $book;
        }

        usort($filtered, static fn (Book $a, Book $b): int => strcmp($a->title(), $b->title()));

        return new Page(
            array_slice($filtered, ($page - 1) * $size, $size),
            count($filtered),
            $page,
            $size,
        );
    }
}
