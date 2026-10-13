<?php

declare(strict_types=1);

namespace Tests\Unit\Application;

use App\Application\Exceptions\NotFound;
use App\Application\Services\CreateBookService;
use App\Application\Services\CreateMemberService;
use App\Application\Services\LendBooksService;
use App\Application\Services\ReturnItemService;
use App\Domain\Exceptions\ItemAlreadyReturned;
use App\Domain\LoanStatus;
use PHPUnit\Framework\TestCase;
use Tests\Fakes\InMemoryBookRepository;
use Tests\Fakes\InMemoryCategoryReader;
use Tests\Fakes\InMemoryLoanRepository;
use Tests\Fakes\InMemoryMemberRepository;
use Tests\Support\FakeClock;

final class ReturnItemServiceTest extends TestCase
{
    private InMemoryBookRepository $books;
    private InMemoryLoanRepository $loans;
    private FakeClock $clock;
    private LendBooksService $lend;
    private ReturnItemService $service;
    private string $memberId;

    protected function setUp(): void
    {
        $this->books = new InMemoryBookRepository();
        $this->loans = new InMemoryLoanRepository();
        $members = new InMemoryMemberRepository();
        $this->clock = FakeClock::at('2026-10-14T14:15:00Z');

        $this->lend = new LendBooksService(
            $this->loans,
            $this->books,
            $members,
            new InMemoryCategoryReader(['cat-1' => 'Novela']),
            $this->clock,
        );
        $this->service = new ReturnItemService($this->loans, $this->books, $this->clock);

        $this->memberId = (new CreateMemberService($members))->create('Ana Torres', '123456789')->id();
    }

    private function makeBook(string $isbn, int $total = 1): \App\Domain\Book
    {
        return (new CreateBookService($this->books))->create("Título $isbn", 'Autor', $isbn, 'cat-1', $total);
    }

    public function test_returning_a_line_sets_returned_at_and_restocks(): void
    {
        $book = $this->makeBook('9780307474728');
        $loan = $this->lend->lend('user-1', $this->memberId, [$book->id()]);
        $itemId = $loan->items()[0]->id();

        $returned = $this->service->returnItem($loan->id(), $itemId);

        self::assertTrue($returned->isReturned());
        self::assertSame(1, $book->availableCopies());
    }

    public function test_returning_twice_never_restocks_twice(): void
    {
        $book = $this->makeBook('9780307474728');
        $loan = $this->lend->lend('user-1', $this->memberId, [$book->id()]);
        $itemId = $loan->items()[0]->id();

        try {
            $this->service->returnItem($loan->id(), $itemId);
            $this->service->returnItem($loan->id(), $itemId);
            self::fail('Se esperaba ItemAlreadyReturned');
        } catch (ItemAlreadyReturned) {
            self::assertSame(1, $book->availableCopies());
        }
    }

    public function test_a_loan_closes_when_every_line_is_returned(): void
    {
        $book = $this->makeBook('9780307474728', 2);
        $loan = $this->lend->lend('user-1', $this->memberId, [$book->id()]);
        $this->service->returnItem($loan->id(), $loan->items()[0]->id());

        self::assertSame(LoanStatus::Closed, $loan->statusAt($this->clock->now()));
    }

    public function test_unknown_loan_or_line_is_not_found(): void
    {
        $book = $this->makeBook('9780307474728');
        $loan = $this->lend->lend('user-1', $this->memberId, [$book->id()]);

        try {
            $this->service->returnItem('no-existe', $loan->items()[0]->id());
            self::fail('Se esperaba NotFound');
        } catch (NotFound) {
        }

        $this->expectException(NotFound::class);
        $this->service->returnItem($loan->id(), 'no-existe');
    }
}
