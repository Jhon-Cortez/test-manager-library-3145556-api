<?php

declare(strict_types=1);

namespace Tests\Unit\Application;

use App\Application\Exceptions\MemberHasOverdue;
use App\Application\Exceptions\MemberLimitExceeded;
use App\Application\Exceptions\NotFound;
use App\Application\Services\CreateBookService;
use App\Application\Services\CreateMemberService;
use App\Application\Services\LendBooksService;
use App\Domain\Exceptions\BookNotAvailable;
use App\Domain\Exceptions\DuplicateBookInLoan;
use App\Domain\Exceptions\InvalidLoanSize;
use App\Domain\Loan;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Tests\Fakes\InMemoryBookRepository;
use Tests\Fakes\InMemoryCategoryReader;
use Tests\Fakes\InMemoryLoanRepository;
use Tests\Fakes\InMemoryMemberRepository;
use Tests\Support\FakeClock;

final class LendBooksServiceTest extends TestCase
{
    private InMemoryBookRepository $books;
    private InMemoryLoanRepository $loans;
    private InMemoryMemberRepository $members;
    private FakeClock $clock;
    private LendBooksService $service;
    private string $memberId;

    protected function setUp(): void
    {
        $this->books = new InMemoryBookRepository();
        $this->loans = new InMemoryLoanRepository();
        $this->members = new InMemoryMemberRepository();
        $this->clock = FakeClock::at('2026-10-14T14:15:00Z');

        $this->service = new LendBooksService(
            $this->loans,
            $this->books,
            $this->members,
            new InMemoryCategoryReader(['cat-1' => 'Novela']),
            $this->clock,
        );

        $member = (new CreateMemberService($this->members))->create('Ana Torres', '123456789');
        $this->memberId = $member->id();
    }

    private function makeBook(string $isbn, int $total = 1): \App\Domain\Book
    {
        return (new CreateBookService($this->books))->create("Título $isbn", 'Autor', $isbn, 'cat-1', $total);
    }

    public function test_registers_a_loan_freezing_values_and_due_date(): void
    {
        $book = $this->makeBook('9780307474728');

        $loan = $this->service->lend('user-1', $this->memberId, [$book->id()]);

        self::assertCount(1, $loan->items());
        $item = $loan->items()[0];
        self::assertSame("Título 9780307474728", $item->bookTitle());
        self::assertSame('9780307474728', $item->isbn()->value());
        self::assertSame('Novela', $item->categoryName());
        self::assertSame('2026-10-28T14:15:00+00:00', $item->dueAt()->format(DATE_ATOM));
        self::assertSame(0, $book->availableCopies());
    }

    public function test_the_same_book_twice_in_a_loan_is_rejected(): void
    {
        $book = $this->makeBook('9780307474728', 2);

        $this->expectException(DuplicateBookInLoan::class);
        $this->service->lend('user-1', $this->memberId, [$book->id(), $book->id()]);
    }

    public function test_without_copies_nothing_is_registered(): void
    {
        $first = $this->makeBook('9780307474728', 1);
        $soldOut = $this->makeBook('9780307474729', 1);
        $soldOut->withdraw();

        try {
            $this->service->lend('user-1', $this->memberId, [$first->id(), $soldOut->id()]);
            self::fail('Se esperaba BookNotAvailable');
        } catch (BookNotAvailable) {
            self::assertSame(0, $this->loans->count());
        }
    }

    public function test_a_member_cannot_exceed_three_unreturned_books(): void
    {
        $book = $this->makeBook('9780307474728', 3);
        $this->service->lend('user-1', $this->memberId, [$book->id()]);
        $this->service->lend('user-1', $this->memberId, [$book->id()]);
        $this->service->lend('user-1', $this->memberId, [$book->id()]);

        try {
            $this->service->lend('user-1', $this->memberId, [$book->id()]);
            self::fail('Se esperaba MemberLimitExceeded');
        } catch (MemberLimitExceeded $e) {
            self::assertStringContainsString('3', $e->getMessage());
            self::assertSame(3, $this->loans->count());
        }
    }

    public function test_a_member_with_overdue_books_cannot_borrow(): void
    {
        $book = $this->makeBook('9780307474728', 1);
        $this->service->lend('user-1', $this->memberId, [$book->id()]);
        $this->clock->advance('+15 days');

        $this->expectException(MemberHasOverdue::class);
        $this->service->lend('user-1', $this->memberId, [$this->makeBook('9780307474729', 1)->id()]);
    }

    public function test_a_loan_needs_between_one_and_three_lines(): void
    {
        $this->expectException(InvalidLoanSize::class);
        $this->service->lend('user-1', $this->memberId, []);
    }

    public function test_unknown_member_or_book_is_not_found(): void
    {
        try {
            $this->service->lend('user-1', 'no-existe', [$this->makeBook('9780307474728')->id()]);
            self::fail('Se esperaba NotFound');
        } catch (NotFound) {
        }

        $this->expectException(NotFound::class);
        $this->service->lend('user-1', $this->memberId, ['no-existe']);
    }
}
