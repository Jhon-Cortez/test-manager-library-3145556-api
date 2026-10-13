<?php

declare(strict_types=1);

namespace Tests\Unit\Domain;

use App\Domain\Book;
use App\Domain\Exceptions\DuplicateBookInLoan;
use App\Domain\Exceptions\InvalidLoanSize;
use App\Domain\Exceptions\ItemAlreadyReturned;
use App\Domain\Isbn;
use App\Domain\Loan;
use App\Domain\LoanStatus;
use App\Domain\Quantity;
use PHPUnit\Framework\TestCase;
use Tests\Support\FakeClock;

final class LoanTest extends TestCase
{
    private function makeBook(string $id, int $total = 2): Book
    {
        return Book::create($id, "Título $id", 'Autor', Isbn::of('9780307474728'), 'cat-1', Quantity::of($total));
    }

    private function makeLoan(FakeClock $clock): Loan
    {
        return Loan::register('loan-1', 'member-1', 'user-1', $clock->now());
    }

    public function test_due_date_is_loan_date_plus_fourteen_days(): void
    {
        $clock = FakeClock::at('2026-10-14T14:15:00Z');
        $loan = $this->makeLoan($clock);

        $item = $loan->addItem('item-1', $this->makeBook('book-1'), 'Novela');

        self::assertSame('2026-10-28T14:15:00+00:00', $item->dueAt()->format(DATE_ATOM));
    }

    public function test_adding_an_item_freezes_catalog_values_and_withdraws(): void
    {
        $clock = FakeClock::at('2026-10-14T14:15:00Z');
        $loan = $this->makeLoan($clock);
        $book = $this->makeBook('book-1', 1);

        $item = $loan->addItem('item-1', $book, 'Novela');

        self::assertSame('Título book-1', $item->bookTitle());
        self::assertSame('9780307474728', $item->isbn()->value());
        self::assertSame('Novela', $item->categoryName());
        self::assertSame(0, $book->availableCopies());
    }

    public function test_a_loan_holds_at_most_three_items(): void
    {
        $clock = FakeClock::at('2026-10-14T14:15:00Z');
        $loan = $this->makeLoan($clock);
        $loan->addItem('item-1', $this->makeBook('book-1'), 'Novela');
        $loan->addItem('item-2', $this->makeBook('book-2'), 'Novela');
        $loan->addItem('item-3', $this->makeBook('book-3'), 'Novela');

        $this->expectException(InvalidLoanSize::class);
        $loan->addItem('item-4', $this->makeBook('book-4'), 'Novela');
    }

    public function test_the_same_book_cannot_appear_twice(): void
    {
        $clock = FakeClock::at('2026-10-14T14:15:00Z');
        $loan = $this->makeLoan($clock);
        $book = $this->makeBook('book-1', 2);
        $loan->addItem('item-1', $book, 'Novela');

        try {
            $loan->addItem('item-2', $book, 'Novela');
            self::fail('Se esperaba DuplicateBookInLoan');
        } catch (DuplicateBookInLoan) {
            self::assertSame(1, $book->availableCopies());
        }
    }

    public function test_returning_an_item_restocks_once_and_closes_when_all_returned(): void
    {
        $clock = FakeClock::at('2026-10-14T14:15:00Z');
        $loan = $this->makeLoan($clock);
        $book = $this->makeBook('book-1', 1);
        $item = $loan->addItem('item-1', $book, 'Novela');

        $loan->returnItem('item-1', $book, $clock->now());

        self::assertSame(1, $book->availableCopies());
        self::assertTrue($item->isReturned());
        self::assertSame(LoanStatus::Closed, $loan->statusAt($clock->now()));
    }

    public function test_returning_twice_never_restocks_twice(): void
    {
        $clock = FakeClock::at('2026-10-14T14:15:00Z');
        $loan = $this->makeLoan($clock);
        $book = $this->makeBook('book-1', 1);
        $loan->addItem('item-1', $book, 'Novela');

        try {
            $loan->returnItem('item-1', $book, $clock->now());
            $loan->returnItem('item-1', $book, $clock->now());
            self::fail('Se esperaba ItemAlreadyReturned');
        } catch (ItemAlreadyReturned) {
            self::assertSame(1, $book->availableCopies());
        }
    }

    public function test_status_is_open_while_items_are_not_due(): void
    {
        $clock = FakeClock::at('2026-10-14T14:15:00Z');
        $loan = $this->makeLoan($clock);
        $loan->addItem('item-1', $this->makeBook('book-1'), 'Novela');

        self::assertSame(LoanStatus::Open, $loan->statusAt($clock->now()));
    }

    public function test_status_is_overdue_when_an_item_passes_its_due_date(): void
    {
        $clock = FakeClock::at('2026-10-14T14:15:00Z');
        $loan = $this->makeLoan($clock);
        $loan->addItem('item-1', $this->makeBook('book-1'), 'Novela');

        $clock->advance('+15 days');

        self::assertSame(LoanStatus::Overdue, $loan->statusAt($clock->now()));
    }

    public function test_status_is_closed_only_when_every_item_is_returned(): void
    {
        $clock = FakeClock::at('2026-10-14T14:15:00Z');
        $loan = $this->makeLoan($clock);
        $book = $this->makeBook('book-1', 2);
        $loan->addItem('item-1', $book, 'Novela');
        $loan->addItem('item-2', $this->makeBook('book-2'), 'Ciencia');

        $loan->returnItem('item-1', $book, $clock->now());

        self::assertSame(LoanStatus::Open, $loan->statusAt($clock->now()));

        $clock->advance('+15 days');

        self::assertSame(LoanStatus::Overdue, $loan->statusAt($clock->now()));
    }
}
