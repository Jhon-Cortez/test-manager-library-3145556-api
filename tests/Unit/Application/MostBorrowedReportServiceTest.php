<?php

declare(strict_types=1);

namespace Tests\Unit\Application;

use App\Application\Services\CreateBookService;
use App\Application\Services\CreateMemberService;
use App\Application\Services\LendBooksService;
use App\Application\Services\MostBorrowedReportService;
use App\Domain\DateRange;
use App\Domain\Exceptions\InvalidValue;
use App\Domain\Quantity;
use PHPUnit\Framework\TestCase;
use Tests\Fakes\InMemoryBookRepository;
use Tests\Fakes\InMemoryCategoryReader;
use Tests\Fakes\InMemoryLoanRepository;
use Tests\Fakes\InMemoryMemberRepository;
use Tests\Fakes\InMemoryReportReader;
use Tests\Support\FakeClock;

final class MostBorrowedReportServiceTest extends TestCase
{
    private InMemoryBookRepository $books;
    private InMemoryLoanRepository $loans;
    private FakeClock $clock;
    private LendBooksService $lend;
    private MostBorrowedReportService $service;
    private string $memberId;

    protected function setUp(): void
    {
        $this->books = new InMemoryBookRepository();
        $this->loans = new InMemoryLoanRepository();
        $members = new InMemoryMemberRepository();
        $this->clock = FakeClock::at('2026-10-05T10:00:00Z');

        $this->lend = new LendBooksService(
            $this->loans,
            $this->books,
            $members,
            new InMemoryCategoryReader(['cat-1' => 'Novela', 'cat-2' => 'Ciencia']),
            $this->clock,
        );
        $this->service = new MostBorrowedReportService(new InMemoryReportReader($this->loans));
        $this->memberId = (new CreateMemberService($members))->create('Ana Torres', '123456789')->id();
    }

    private function makeBook(string $isbn, int $total = 3): \App\Domain\Book
    {
        return (new CreateBookService($this->books))->create("Título $isbn", 'Autor', $isbn, 'cat-1', $total);
    }

    private function range(): DateRange
    {
        return DateRange::of(
            new \DateTimeImmutable('2026-10-01T00:00:00Z'),
            new \DateTimeImmutable('2026-10-31T23:59:59Z'),
        );
    }

    public function test_one_row_per_book_and_frozen_category_sorted_by_times_borrowed(): void
    {
        $popular = $this->makeBook('9780307474728');
        $other = $this->makeBook('9780307474729');
        $this->lend->lend('user-1', $this->memberId, [$popular->id()]);
        $this->lend->lend('user-1', $this->memberId, [$popular->id()]);
        $this->lend->lend('user-1', $this->memberId, [$other->id()]);

        $rows = $this->service->mostBorrowed($this->range());

        self::assertCount(2, $rows);
        self::assertSame($popular->id(), $rows[0]['book_id']);
        self::assertSame(2, $rows[0]['times_borrowed']);
        self::assertSame('Novela', $rows[0]['category_name']);
        self::assertSame(1, $rows[1]['times_borrowed']);
    }

    public function test_renaming_a_book_does_not_change_a_closed_period_report(): void
    {
        $book = $this->makeBook('9780307474728');
        $this->lend->lend('user-1', $this->memberId, [$book->id()]);

        $book->update('Título nuevo', 'Autor', 'cat-1', Quantity::of(3));

        $rows = $this->service->mostBorrowed($this->range());

        self::assertSame("Título 9780307474728", $rows[0]['book_title']);
    }

    public function test_a_book_lent_under_two_categories_produces_two_rows(): void
    {
        $book = $this->makeBook('9780307474728');
        $this->lend->lend('user-1', $this->memberId, [$book->id()]);

        $book->update("Título 9780307474728", 'Autor', 'cat-2', Quantity::of(3));
        $this->lend->lend('user-1', $this->memberId, [$book->id()]);

        $rows = $this->service->mostBorrowed($this->range());

        self::assertCount(2, $rows);
        self::assertSame('Novela', $rows[0]['category_name']);
        self::assertSame('Ciencia', $rows[1]['category_name']);
    }

    public function test_a_range_without_loans_returns_an_empty_list(): void
    {
        $rows = $this->service->mostBorrowed(
            DateRange::of(
                new \DateTimeImmutable('2026-01-01T00:00:00Z'),
                new \DateTimeImmutable('2026-01-31T00:00:00Z'),
            ),
        );

        self::assertSame([], $rows);
    }

    public function test_an_inverted_range_is_rejected(): void
    {
        $this->expectException(InvalidValue::class);

        DateRange::of(
            new \DateTimeImmutable('2026-10-31T00:00:00Z'),
            new \DateTimeImmutable('2026-10-01T00:00:00Z'),
        );
    }
}
