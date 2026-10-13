<?php

declare(strict_types=1);

namespace Tests\Unit\Domain;

use App\Domain\Book;
use App\Domain\Exceptions\BookNotAvailable;
use App\Domain\Exceptions\InvalidValue;
use App\Domain\Isbn;
use App\Domain\Quantity;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class BookTest extends TestCase
{
    private function makeBook(int $total = 3): Book
    {
        return Book::create(
            'book-1',
            'Cien años de soledad',
            'Gabriel García Márquez',
            Isbn::of('9780307474728'),
            'cat-1',
            Quantity::of($total),
        );
    }

    public function test_available_start_equal_to_total_and_version_one(): void
    {
        $book = $this->makeBook(4);

        self::assertSame(4, $book->availableCopies());
        self::assertSame(4, $book->totalCopies());
        self::assertSame(1, $book->version());
    }

    public function test_total_must_be_at_least_one(): void
    {
        $this->expectException(InvalidValue::class);

        $this->makeBook(0);
    }

    public function test_title_cannot_be_empty(): void
    {
        $this->expectException(InvalidValue::class);

        Book::create('book-1', '   ', 'Autor', Isbn::of('9780307474728'), 'cat-1', Quantity::of(1));
    }

    public function test_withdraw_decrements_and_bumps_version(): void
    {
        $book = $this->makeBook(1);

        $book->withdraw();

        self::assertSame(0, $book->availableCopies());
        self::assertSame(2, $book->version());
    }

    public function test_available_never_goes_below_zero(): void
    {
        $book = $this->makeBook(1);
        $book->withdraw();

        $this->expectException(BookNotAvailable::class);
        $book->withdraw();
    }

    public function test_restock_never_exceeds_total(): void
    {
        $book = $this->makeBook(1);

        $this->expectException(InvalidValue::class);
        $book->restock();
    }

    public function test_restock_returns_one(): void
    {
        $book = $this->makeBook(2);
        $book->withdraw();

        $book->restock();

        self::assertSame(2, $book->availableCopies());
    }

    public function test_changing_total_adjusts_available_by_the_same_difference(): void
    {
        $book = $this->makeBook(4);
        $book->withdraw();

        $book->update('Cien años de soledad', 'Gabriel García Márquez', 'cat-1', Quantity::of(3));

        self::assertSame(3, $book->totalCopies());
        self::assertSame(2, $book->availableCopies());
    }

    public function test_changing_total_can_leave_available_at_zero(): void
    {
        $book = $this->makeBook(3);
        $book->withdraw();
        $book->withdraw();

        $book->update('Título', 'Autor', 'cat-1', Quantity::of(2));

        self::assertSame(0, $book->availableCopies());
        self::assertSame(2, $book->totalCopies());
    }

    public function test_changing_total_never_leaves_available_below_zero(): void
    {
        $book = $this->makeBook(2);
        $book->withdraw();
        $book->withdraw();

        $this->expectException(InvalidValue::class);
        $book->update('Título', 'Autor', 'cat-1', Quantity::of(1));
    }

    public function test_cover_key_is_null_or_an_opaque_string(): void
    {
        $book = $this->makeBook(1);

        self::assertNull($book->coverKey());

        $book->setCoverKey('covers/ab/cd/ef.png');
        self::assertSame('covers/ab/cd/ef.png', $book->coverKey());

        $this->expectException(InvalidValue::class);
        $book->setCoverKey('   ');
    }

    public function test_clearing_cover_sets_null_never_empty_string(): void
    {
        $book = $this->makeBook(1);
        $book->setCoverKey('covers/ab/cd/ef.png');

        $book->clearCover();

        self::assertNull($book->coverKey());
    }

    public function test_logical_deletion_keeps_the_book_record(): void
    {
        $book = $this->makeBook(1);
        $now = new DateTimeImmutable('2026-10-13T12:00:00Z');

        $book->markDeleted($now);

        self::assertTrue($book->isDeleted());
        self::assertSame($now, $book->deletedAt());
    }
}
