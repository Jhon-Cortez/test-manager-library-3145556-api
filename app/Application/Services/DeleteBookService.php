<?php

declare(strict_types=1);

namespace App\Application\Services;

use App\Application\Exceptions\BookHasOpenLoans;
use App\Application\Exceptions\NotFound;
use App\Application\Ports\Outbound\BookRepository;
use App\Application\Ports\Outbound\Clock;
use App\Application\Ports\Outbound\LoanRepository;
use App\Domain\Book;

final class DeleteBookService
{
    public function __construct(
        private readonly BookRepository $books,
        private readonly LoanRepository $loans,
        private readonly Clock $clock,
    ) {
    }

    public function delete(string $bookId): Book
    {
        $book = $this->books->findById($bookId);
        if ($book === null || $book->isDeleted()) {
            throw new NotFound('el libro');
        }

        $withLines = $this->loans->bookIdsWithUnreturnedItems([$book->id()]);
        if ($withLines !== []) {
            throw new BookHasOpenLoans();
        }

        $book->markDeleted($this->clock->now());
        $this->books->save($book);

        return $book;
    }
}
