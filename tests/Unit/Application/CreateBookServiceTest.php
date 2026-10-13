<?php

declare(strict_types=1);

namespace Tests\Unit\Application;

use App\Application\Exceptions\IsbnTaken;
use App\Application\Services\CreateBookService;
use App\Domain\Exceptions\InvalidValue;
use PHPUnit\Framework\TestCase;
use Tests\Fakes\InMemoryBookRepository;

final class CreateBookServiceTest extends TestCase
{
    private InMemoryBookRepository $books;
    private CreateBookService $service;

    protected function setUp(): void
    {
        $this->books = new InMemoryBookRepository();
        $this->service = new CreateBookService($this->books);
    }

    public function test_available_copies_start_equal_to_total(): void
    {
        $book = $this->service->create('Cien años de soledad', 'Gabriel García Márquez', '9780307474728', 'cat-1', 4);

        self::assertSame(4, $book->totalCopies());
        self::assertSame(4, $book->availableCopies());
        self::assertSame('9780307474728', $book->isbn()->value());
    }

    public function test_duplicated_isbn_among_active_books_is_rejected(): void
    {
        $this->service->create('Libro A', 'Autor', '9780307474728', 'cat-1', 1);

        $this->expectException(IsbnTaken::class);
        $this->service->create('Libro B', 'Autor', '9780307474728', 'cat-1', 1);
    }

    public function test_isbn_of_a_deleted_book_can_be_reused(): void
    {
        $first = $this->service->create('Libro A', 'Autor', '9780307474728', 'cat-1', 1);
        $first->markDeleted(new \DateTimeImmutable('2026-10-13T12:00:00Z'));

        $second = $this->service->create('Libro B', 'Autor', '9780307474728', 'cat-1', 1);

        self::assertSame('Libro B', $second->title());
    }

    public function test_total_must_be_at_least_one(): void
    {
        $this->expectException(InvalidValue::class);

        $this->service->create('Libro', 'Autor', '9780307474728', 'cat-1', 0);
    }

    public function test_invalid_isbn_is_rejected(): void
    {
        $this->expectException(InvalidValue::class);

        $this->service->create('Libro', 'Autor', '123', 'cat-1', 1);
    }
}
