<?php

declare(strict_types=1);

namespace App\Application\Services;

use App\Application\Exceptions\MemberHasOverdue;
use App\Application\Exceptions\MemberLimitExceeded;
use App\Application\Exceptions\NotFound;
use App\Application\Ports\Outbound\BookRepository;
use App\Application\Ports\Outbound\CategoryReader;
use App\Application\Ports\Outbound\Clock;
use App\Application\Ports\Outbound\LoanRepository;
use App\Application\Ports\Outbound\MemberRepository;
use App\Application\Support\Uuid;
use App\Domain\Book;
use App\Domain\Exceptions\InvalidLoanSize;
use App\Domain\Loan;

final class LendBooksService
{
    public const MAX_UNRETURNED_PER_MEMBER = 3;

    public function __construct(
        private readonly LoanRepository $loans,
        private readonly BookRepository $books,
        private readonly MemberRepository $members,
        private readonly CategoryReader $categories,
        private readonly Clock $clock,
    ) {
    }

    /** @param string[] $bookIds */
    public function lend(string $actingUserId, string $memberId, array $bookIds): Loan
    {
        $bookIds = array_values($bookIds);
        if (count($bookIds) < 1 || count($bookIds) > 3) {
            throw new InvalidLoanSize('Un préstamo admite de 1 a 3 líneas.');
        }
        if ($this->members->findById($memberId) === null) {
            throw new NotFound('el socio');
        }

        $now = $this->clock->now();
        if ($this->loans->hasOverdueItemsByMember($memberId, $now)) {
            throw new MemberHasOverdue();
        }

        $unreturned = $this->loans->countUnreturnedItemsByMember($memberId);
        if ($unreturned + count($bookIds) > self::MAX_UNRETURNED_PER_MEMBER) {
            throw new MemberLimitExceeded($unreturned);
        }

        $loan = Loan::register(Uuid::new(), $memberId, $actingUserId, $now);
        $touched = [];
        foreach ($bookIds as $bookId) {
            $book = $this->books->findById($bookId);
            if ($book === null || $book->isDeleted()) {
                throw new NotFound('el libro');
            }
            $loan->addItem(Uuid::new(), $book, $this->categoryNameOf($book));
            $touched[$book->id()] = $book;
        }

        $this->loans->add($loan);
        foreach ($touched as $book) {
            $this->books->save($book);
        }

        return $loan;
    }

    private function categoryNameOf(Book $book): string
    {
        $name = $this->categories->nameOf($book->categoryId());
        if ($name === null) {
            throw new NotFound('la categoría');
        }

        return $name;
    }

}
