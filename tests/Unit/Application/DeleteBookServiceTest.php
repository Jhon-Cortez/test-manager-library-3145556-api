<?php

declare(strict_types=1);

namespace Tests\Unit\Application;

use App\Application\Exceptions\BookHasOpenLoans;
use App\Application\Services\CreateBookService;
use App\Application\Services\DeleteBookService;
use App\Domain\Loan;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Tests\Fakes\InMemoryBookRepository;
use Tests\Fakes\InMemoryLoanRepository;
use Tests\Support\FakeClock;

final class DeleteBookServiceTest extends TestCase
{
    private InMemoryBookRepository $books;
    private InMemoryLoanRepository $loans;
    private CreateBookService $create;
    private DeleteBookService $service;
    private FakeClock $clock;

    protected function setUp(): void
    {
        $this->books = new InMemoryBookRepository();
        $this->loans = new InMemoryLoanRepository();
        $this->clock = FakeClock::at('2026-10-13T12:00:00Z');
        $this->create = new CreateBookService($this->books);
        $this->service = new DeleteBookService($this->books, $this->loans, $this->clock);
    }

    public function test_logical_deletion_hides_the_book_from_the_catalog(): void
    {
        $book = $this->create->create('Título', 'Autor', '9780307474728', 'cat-1', 1);

        $deleted = $this->service->delete($book->id());

        self::assertTrue($deleted->isDeleted());
        self::assertSame($this->clock->now(), $deleted->deletedAt());
    }

    public function test_a_book_with_unreturned_lines_cannot_be_deleted(): void
    {
        $book = $this->create->create('Título', 'Autor', '9780307474728', 'cat-1', 1);
        $loan = Loan::register('loan-1', 'member-1', 'user-1', $this->clock->now());
        $loan->addItem('item-1', $book, 'Novela');
        $this->loans->add($loan);

        $this->expectException(BookHasOpenLoans::class);
        $this->service->delete($book->id());
    }

    public function test_a_book_whose_lines_are_all_returned_can_be_deleted(): void
    {
        $book = $this->create->create('Título', 'Autor', '9780307474728', 'cat-1', 1);
        $loan = Loan::register('loan-1', 'member-1', 'user-1', $this->clock->now());
        $loan->addItem('item-1', $book, 'Novela');
        $loan->returnItem('item-1', $book, $this->clock->now());
        $this->loans->add($loan);

        $deleted = $this->service->delete($book->id());

        self::assertTrue($deleted->isDeleted());
    }
}
