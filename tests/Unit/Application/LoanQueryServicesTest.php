<?php

declare(strict_types=1);

namespace Tests\Unit\Application;

use App\Application\Exceptions\NotFound;
use App\Application\Services\CreateBookService;
use App\Application\Services\CreateMemberService;
use App\Application\Services\GetLoanService;
use App\Application\Services\LendBooksService;
use App\Application\Services\ListLoansService;
use App\Application\Services\OverdueService;
use App\Application\Services\ReturnItemService;
use App\Domain\DateRange;
use App\Domain\LoanStatus;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Tests\Fakes\InMemoryBookRepository;
use Tests\Fakes\InMemoryCategoryReader;
use Tests\Fakes\InMemoryLoanRepository;
use Tests\Fakes\InMemoryMemberRepository;
use Tests\Support\FakeClock;

final class LoanQueryServicesTest extends TestCase
{
    private InMemoryBookRepository $books;
    private InMemoryLoanRepository $loans;
    private InMemoryMemberRepository $members;
    private FakeClock $clock;
    private LendBooksService $lend;
    private GetLoanService $get;
    private ListLoansService $list;
    private OverdueService $overdue;

    protected function setUp(): void
    {
        $this->books = new InMemoryBookRepository();
        $this->loans = new InMemoryLoanRepository();
        $this->members = new InMemoryMemberRepository();
        $this->clock = FakeClock::at('2026-10-14T14:15:00Z');

        $this->lend = new LendBooksService(
            $this->loans,
            $this->books,
            $this->members,
            new InMemoryCategoryReader(['cat-1' => 'Novela']),
            $this->clock,
        );
        $this->get = new GetLoanService($this->loans);
        $this->list = new ListLoansService($this->loans, $this->clock);
        $this->overdue = new OverdueService($this->loans, $this->members, $this->clock);
    }

    private function makeBook(string $isbn, int $total = 1): \App\Domain\Book
    {
        return (new CreateBookService($this->books))->create("Título $isbn", 'Autor', $isbn, 'cat-1', $total);
    }

    public function test_detail_includes_lines_and_calculated_status(): void
    {
        $member = (new CreateMemberService($this->members))->create('Ana Torres', '123456789');
        $book = $this->makeBook('9780307474728');
        $loan = $this->lend->lend('user-1', $member->id(), [$book->id()]);

        $found = $this->get->get($loan->id());

        self::assertCount(1, $found->items());
        self::assertSame(LoanStatus::Open, $found->statusAt($this->clock->now()));
    }

    public function test_missing_loan_is_not_found(): void
    {
        $this->expectException(NotFound::class);

        $this->get->get('no-existe');
    }

    public function test_listing_filters_by_member_status_and_date_and_is_paginated(): void
    {
        $memberA = (new CreateMemberService($this->members))->create('Ana Torres', '123456789');
        $memberB = (new CreateMemberService($this->members))->create('Luis Rojas', '987654321');
        $bookA = $this->makeBook('9780307474728');
        $bookB = $this->makeBook('9780307474729');
        $bookC = $this->makeBook('9780307474730');

        $first = $this->lend->lend('user-1', $memberA->id(), [$bookA->id()]);
        $this->clock->advance('+1 day');
        $second = $this->lend->lend('user-1', $memberB->id(), [$bookB->id()]);
        $this->clock->advance('+1 day');
        $third = $this->lend->lend('user-1', $memberA->id(), [$bookC->id()]);

        $byMember = $this->list->list(null, null, $memberA->id(), 1, 10);
        self::assertSame(2, $byMember->total());
        self::assertSame($third->id(), $byMember->items()[0]->id());

        $range = DateRange::of(
            new DateTimeImmutable('2026-10-14T00:00:00Z'),
            new DateTimeImmutable('2026-10-16T00:00:00Z'),
        );
        $byRange = $this->list->list($range, null, null, 1, 10);
        self::assertSame(2, $byRange->total());

        $open = $this->list->list(null, LoanStatus::Open, null, 1, 1);
        self::assertSame(3, $open->total());
        self::assertCount(1, $open->items());

        $return = new ReturnItemService($this->loans, $this->books, $this->clock);
        $return->returnItem($first->id(), $first->items()[0]->id());

        $closed = $this->list->list(null, LoanStatus::Closed, null, 1, 10);
        self::assertSame(1, $closed->total());
        self::assertSame($first->id(), $closed->items()[0]->id());
    }

    public function test_overdue_lists_unreturned_lines_past_due_sorted_by_delay(): void
    {
        $member = (new CreateMemberService($this->members))->create('Ana Torres', '123456789');
        $early = $this->lend->lend('user-1', $member->id(), [$this->makeBook('9780307474728')->id()]);
        $this->clock->advance('+2 days');
        $this->lend->lend('user-1', $member->id(), [$this->makeBook('9780307474729')->id()]);
        $this->clock->advance('+15 days');

        $rows = $this->overdue->overdue();

        self::assertCount(2, $rows);
        self::assertSame($early->items()[0]->id(), $rows[0]['item_id']);
        self::assertGreaterThan($rows[1]['days_late'], $rows[0]['days_late']);
        self::assertSame('Ana Torres', $rows[0]['member_name']);
        self::assertSame('2026-10-28T14:15:00+00:00', $rows[0]['due_at']->format(DATE_ATOM));
    }
}
