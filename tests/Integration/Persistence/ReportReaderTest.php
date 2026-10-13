<?php

declare(strict_types=1);

namespace Tests\Integration\Persistence;

use App\Adapters\Outbound\Persistence\EloquentBookRepository;
use App\Adapters\Outbound\Persistence\EloquentLoanRepository;
use App\Adapters\Outbound\Persistence\EloquentMemberRepository;
use App\Adapters\Outbound\Persistence\EloquentReportReader;
use App\Adapters\Outbound\Persistence\EloquentUserRepository;
use App\Domain\Book;
use App\Domain\DateRange;
use App\Domain\DocumentNumber;
use App\Domain\Isbn;
use App\Domain\Loan;
use App\Domain\Member;
use App\Domain\Quantity;
use App\Domain\Role;
use App\Domain\User;
use DateTimeImmutable;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

final class ReportReaderTest extends TestCase
{
    use DatabaseTransactions;

    private const FROM = '2026-10-01T00:00:00Z';
    private const TO = '2026-10-31T23:59:59Z';

    private function range(): DateRange
    {
        return DateRange::of(new DateTimeImmutable(self::FROM), new DateTimeImmutable(self::TO));
    }

    public function test_renaming_a_book_does_not_change_the_report(): void
    {
        $books = new EloquentBookRepository();
        $loans = new EloquentLoanRepository();
        (new EloquentMemberRepository())->add(
            Member::register('cccc3333-cccc-4ccc-8ccc-333333333333', 'Ana Torres', DocumentNumber::of('123456789')),
        );
        (new EloquentUserRepository())->add(
            User::register('dddd4444-dddd-4ddd-8ddd-444444444444', 'ana', 'hashed:secret', Role::Librarian),
        );

        $book = Book::create('aaaa1111-aaaa-4aaa-8aaa-111111111111', 'Título viejo', 'Autor', Isbn::of('9780307474728'), '11111111-1111-4111-8111-111111111111', Quantity::of(2));
        $books->add($book);

        $loan = Loan::register('bbbb2222-bbbb-4bbb-8bbb-222222222222', 'cccc3333-cccc-4ccc-8ccc-333333333333', 'dddd4444-dddd-4ddd-8ddd-444444444444', new DateTimeImmutable('2026-10-14T14:15:00Z'));
        $loan->addItem('eeee5555-eeee-4eee-8eee-555555555555', $book, 'Novela');
        $books->save($book);
        $loans->add($loan);

        $book = $books->findById($book->id());
        $book->update('Título nuevo', 'Autor', '11111111-1111-4111-8111-111111111111', Quantity::of(2));
        $books->save($book);

        $rows = (new EloquentReportReader())->mostBorrowed($this->range());

        self::assertCount(1, $rows);
        self::assertSame('Título viejo', $rows[0]['book_title']);
        self::assertSame('Novela', $rows[0]['category_name']);
        self::assertSame(1, $rows[0]['times_borrowed']);
    }

    public function test_a_book_lent_under_two_categories_produces_two_rows(): void
    {
        $books = new EloquentBookRepository();
        $loans = new EloquentLoanRepository();
        (new EloquentMemberRepository())->add(
            Member::register('cccc3333-cccc-4ccc-8ccc-333333333333', 'Ana Torres', DocumentNumber::of('123456789')),
        );
        (new EloquentUserRepository())->add(
            User::register('dddd4444-dddd-4ddd-8ddd-444444444444', 'ana', 'hashed:secret', Role::Librarian),
        );

        $book = Book::create('aaaa1111-aaaa-4aaa-8aaa-111111111111', 'Título', 'Autor', Isbn::of('9780307474728'), '11111111-1111-4111-8111-111111111111', Quantity::of(2));
        $books->add($book);

        $first = Loan::register('bbbb2222-bbbb-4bbb-8bbb-222222222222', 'cccc3333-cccc-4ccc-8ccc-333333333333', 'dddd4444-dddd-4ddd-8ddd-444444444444', new DateTimeImmutable('2026-10-05T10:00:00Z'));
        $first->addItem('eeee5555-eeee-4eee-8eee-555555555555', $book, 'Novela');
        $books->save($book);
        $loans->add($first);

        $book = $books->findById($book->id());
        $book->update('Título', 'Autor', '22222222-2222-4222-8222-222222222222', Quantity::of(2));
        $books->save($book);

        $book = $books->findById($book->id());
        $second = Loan::register('bbbb3333-bbbb-4bbb-8bbb-222222222222', 'cccc3333-cccc-4ccc-8ccc-333333333333', 'dddd4444-dddd-4ddd-8ddd-444444444444', new DateTimeImmutable('2026-10-20T10:00:00Z'));
        $second->addItem('eeee6666-eeee-4eee-8eee-555555555555', $book, 'Ciencia');
        $books->save($book);
        $loans->add($second);

        $rows = (new EloquentReportReader())->mostBorrowed($this->range());

        self::assertCount(2, $rows);
        $names = array_column($rows, 'category_name');
        sort($names);
        self::assertSame(['Ciencia', 'Novela'], $names);
    }
}
