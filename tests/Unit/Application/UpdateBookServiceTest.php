<?php

declare(strict_types=1);

namespace Tests\Unit\Application;

use App\Application\Exceptions\NotFound;
use App\Application\Services\CreateBookService;
use App\Application\Services\UpdateBookService;
use App\Domain\Exceptions\InvalidValue;
use PHPUnit\Framework\TestCase;
use Tests\Fakes\InMemoryBookRepository;

final class UpdateBookServiceTest extends TestCase
{
    private InMemoryBookRepository $books;
    private CreateBookService $create;
    private UpdateBookService $service;

    protected function setUp(): void
    {
        $this->books = new InMemoryBookRepository();
        $this->create = new CreateBookService($this->books);
        $this->service = new UpdateBookService($this->books);
    }

    public function test_changing_total_adjusts_available_by_the_same_difference(): void
    {
        $book = $this->create->create('Título', 'Autor', '9780307474728', 'cat-1', 4);
        $book->withdraw();

        $updated = $this->service->update($book->id(), 'Título nuevo', 'Autor', 'cat-1', 3);

        self::assertSame('Título nuevo', $updated->title());
        self::assertSame(3, $updated->totalCopies());
        self::assertSame(2, $updated->availableCopies());
    }

    public function test_total_change_never_leaves_available_below_zero(): void
    {
        $book = $this->create->create('Título', 'Autor', '9780307474728', 'cat-1', 2);
        $book->withdraw();
        $book->withdraw();

        $this->expectException(InvalidValue::class);
        $this->service->update($book->id(), 'Título', 'Autor', 'cat-1', 1);
    }

    public function test_updating_a_missing_book_is_not_found(): void
    {
        $this->expectException(NotFound::class);

        $this->service->update('no-existe', 'Título', 'Autor', 'cat-1', 1);
    }
}
