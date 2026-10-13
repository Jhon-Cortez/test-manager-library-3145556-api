<?php

declare(strict_types=1);

namespace Tests\Integration\Persistence;

use App\Adapters\Outbound\Persistence\EloquentBookRepository;
use App\Adapters\Outbound\Persistence\EloquentLoanRepository;
use App\Adapters\Outbound\Persistence\EloquentMemberRepository;
use App\Adapters\Outbound\Persistence\EloquentUserRepository;
use App\Application\Exceptions\OptimisticLockConflict;
use App\Domain\Book;
use App\Domain\DocumentNumber;
use App\Domain\Exceptions\ItemAlreadyReturned;
use App\Domain\Isbn;
use App\Domain\Loan;
use App\Domain\LoanStatus;
use App\Domain\Member;
use App\Domain\Quantity;
use App\Domain\Role;
use App\Domain\User;
use DateTimeImmutable;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

final class LoanRepositoryTest extends TestCase
{
    use DatabaseTransactions;

    private const CATEGORY_NOVELA = '11111111-1111-4111-8111-111111111111';

    private EloquentBookRepository $books;
    private EloquentLoanRepository $loans;

    protected function setUp(): void
    {
        parent::setUp();
        $this->books = new EloquentBookRepository();
        $this->loans = new EloquentLoanRepository();
    }

    private function makeBook(string $id, int $total = 1): Book
    {
        return Book::create($id, "Título $id", 'Autor', Isbn::of('9780307474728'), self::CATEGORY_NOVELA, Quantity::of($total));
    }

    private function makeMemberAndUser(): void
    {
        (new EloquentMemberRepository())->add(
            Member::register('cccc3333-cccc-4ccc-8ccc-333333333333', 'Ana Torres', DocumentNumber::of('123456789')),
        );
        (new EloquentUserRepository())->add(
            User::register('dddd4444-dddd-4ddd-8ddd-444444444444', 'ana', 'hashed:secret', Role::Librarian),
        );
    }

    public function test_a_loan_with_frozen_items_round_trips(): void
    {
        $this->makeMemberAndUser();
        $book = $this->makeBook('aaaa1111-aaaa-4aaa-8aaa-111111111111');
        $this->books->add($book);

        $loan = Loan::register('bbbb2222-bbbb-4bbb-8bbb-222222222222', 'cccc3333-cccc-4ccc-8ccc-333333333333', 'dddd4444-dddd-4ddd-8ddd-444444444444', new DateTimeImmutable('2026-10-14T14:15:00Z'));
        $loan->addItem('eeee5555-eeee-4eee-8eee-555555555555', $book, 'Novela');
        $this->books->save($book);
        $this->loans->add($loan);

        $found = $this->loans->findById($loan->id());

        self::assertNotNull($found);
        self::assertCount(1, $found->items());
        $item = $found->items()[0];
        self::assertSame("Título aaaa1111-aaaa-4aaa-8aaa-111111111111", $item->bookTitle());
        self::assertSame('Novela', $item->categoryName());
        self::assertSame('2026-10-28T14:15:00+00:00', $item->dueAt()->format(DATE_ATOM));
        self::assertNull($item->returnedAt());
        self::assertSame(LoanStatus::Open, $found->statusAt(new DateTimeImmutable('2026-10-15T00:00:00Z')));
    }

    public function test_returning_persists_returned_at_once(): void
    {
        $this->makeMemberAndUser();
        $book = $this->makeBook('aaaa1111-aaaa-4aaa-8aaa-111111111111');
        $this->books->add($book);
        $loan = Loan::register('bbbb2222-bbbb-4bbb-8bbb-222222222222', 'cccc3333-cccc-4ccc-8ccc-333333333333', 'dddd4444-dddd-4ddd-8ddd-444444444444', new DateTimeImmutable('2026-10-14T14:15:00Z'));
        $loan->addItem('eeee5555-eeee-4eee-8eee-555555555555', $book, 'Novela');
        $this->books->save($book);
        $this->loans->add($loan);

        $loanA = $this->loans->findById($loan->id());
        $bookA = $this->books->findById($book->id());
        $loanB = $this->loans->findById($loan->id());
        $bookB = $this->books->findById($book->id());

        $loanA->returnItem('eeee5555-eeee-4eee-8eee-555555555555', $bookA, new DateTimeImmutable('2026-10-16T09:00:00Z'));
        $this->loans->save($loanA);
        $this->books->save($bookA);

        $found = $this->loans->findById($loan->id());
        self::assertTrue($found->items()[0]->isReturned());
        self::assertSame('2026-10-16T09:00:00+00:00', $found->items()[0]->returnedAt()->format(DATE_ATOM));

        $loanB->returnItem('eeee5555-eeee-4eee-8eee-555555555555', $bookB, new DateTimeImmutable('2026-10-16T10:00:00Z'));

        try {
            $this->loans->save($loanB);
            self::fail('Se esperaba ItemAlreadyReturned');
        } catch (ItemAlreadyReturned) {
            $stored = $this->books->findById($book->id());
            self::assertSame(1, $stored->availableCopies());
        }
    }

    public function test_two_lends_of_the_last_copy_leave_exactly_one_winner(): void
    {
        $book = $this->makeBook('aaaa1111-aaaa-4aaa-8aaa-111111111111', 1);
        $this->books->add($book);

        $firstRequest = $this->books->findById($book->id());
        $secondRequest = $this->books->findById($book->id());

        $firstRequest->withdraw();
        $this->books->save($firstRequest);

        $secondRequest->withdraw();

        try {
            $this->books->save($secondRequest);
            self::fail('Se esperaba OptimisticLockConflict');
        } catch (OptimisticLockConflict) {
            $stored = $this->books->findById($book->id());
            self::assertSame(0, $stored->availableCopies());
            self::assertSame(2, $stored->version());
        }
    }
}
