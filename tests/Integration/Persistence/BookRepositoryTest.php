<?php

declare(strict_types=1);

namespace Tests\Integration\Persistence;

use App\Adapters\Outbound\Persistence\EloquentBookRepository;
use App\Application\Exceptions\OptimisticLockConflict;
use App\Domain\Book;
use App\Domain\Isbn;
use App\Domain\Quantity;
use DateTimeImmutable;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

final class BookRepositoryTest extends TestCase
{
    use DatabaseTransactions;

    private const CATEGORY_NOVELA = '11111111-1111-4111-8111-111111111111';
    private const CATEGORY_CIENCIA = '22222222-2222-4222-8222-222222222222';

    private EloquentBookRepository $books;

    protected function setUp(): void
    {
        parent::setUp();
        $this->books = new EloquentBookRepository();
    }

    private function makeBook(string $id, string $title = 'Cien años de soledad', string $isbn = '9780307474728', int $total = 3, string $categoryId = self::CATEGORY_NOVELA): Book
    {
        return Book::create($id, $title, 'Gabriel García Márquez', Isbn::of($isbn), $categoryId, Quantity::of($total));
    }

    public function test_a_book_round_trips_with_every_column(): void
    {
        $book = $this->makeBook('11111111-aaaa-4aaa-8aaa-111111111111');
        $book->setCoverKey('covers/opaque-key.png');
        $this->books->add($book);

        $found = $this->books->findById($book->id());

        self::assertNotNull($found);
        self::assertSame($book->title(), $found->title());
        self::assertSame($book->isbn()->value(), $found->isbn()->value());
        self::assertSame($book->availableCopies(), $found->availableCopies());
        self::assertSame('covers/opaque-key.png', $found->coverKey());
        self::assertSame($book->version(), $found->version());
    }

    public function test_isbn_is_unique_only_among_active_books(): void
    {
        $book = $this->makeBook('22222222-aaaa-4aaa-8aaa-222222222222');
        $this->books->add($book);

        self::assertTrue($this->books->existsByIsbn('9780307474728'));
        self::assertFalse($this->books->existsByIsbn('9780307474728', $book->id()));

        $book->markDeleted(new DateTimeImmutable('2026-10-13T12:00:00Z'));
        $this->books->save($book);

        self::assertFalse($this->books->existsByIsbn('9780307474728'));
    }

    public function test_search_filters_orders_and_paginates_excluding_deleted(): void
    {
        $this->books->add($this->makeBook('33333333-aaaa-4aaa-8aaa-333333333333', 'Bravo', '9780307474728'));
        $this->books->add($this->makeBook('44444444-aaaa-4aaa-8aaa-444444444444', 'Alfa', '9780307474729', 1, self::CATEGORY_CIENCIA));
        $deleted = $this->makeBook('55555555-aaaa-4aaa-8aaa-555555555555', 'Borrado', '9780307474730');
        $deleted->markDeleted(new DateTimeImmutable('2026-10-13T12:00:00Z'));
        $this->books->add($deleted);

        $all = $this->books->search(null, null, 1, 10);
        self::assertSame(2, $all->total());
        self::assertSame('Alfa', $all->items()[0]->title());

        $byText = $this->books->search('garc', null, 1, 10);
        self::assertSame(2, $byText->total());

        $byCategory = $this->books->search(null, self::CATEGORY_CIENCIA, 1, 10);
        self::assertSame(1, $byCategory->total());
        self::assertSame('Alfa', $byCategory->items()[0]->title());

        $page = $this->books->search(null, null, 1, 1);
        self::assertSame(2, $page->total());
        self::assertCount(1, $page->items());
    }

    public function test_saving_with_a_stale_version_raises_a_conflict(): void
    {
        $book = $this->makeBook('66666666-aaaa-4aaa-8aaa-666666666666');
        $this->books->add($book);

        $stale = $this->books->findById($book->id());
        $fresh = $this->books->findById($book->id());

        $fresh->withdraw();
        $this->books->save($fresh);

        $stale->withdraw();

        $this->expectException(OptimisticLockConflict::class);
        $this->books->save($stale);
    }
}
