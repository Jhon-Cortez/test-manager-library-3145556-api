<?php

declare(strict_types=1);

namespace App\Application\Services;

use App\Application\Exceptions\NotFound;
use App\Application\Ports\Outbound\BookRepository;
use App\Application\Ports\Outbound\CoverStorage;
use App\Domain\Book;

final class RemoveCoverService
{
    public function __construct(
        private readonly BookRepository $books,
        private readonly CoverStorage $storage,
    ) {
    }

    public function remove(string $bookId): Book
    {
        $book = $this->books->findById($bookId);
        if ($book === null || $book->isDeleted()) {
            throw new NotFound('el libro');
        }

        $coverKey = $book->coverKey();
        if ($coverKey === null) {
            return $book;
        }

        $book->clearCover();
        $this->books->save($book);
        $this->storage->delete($coverKey);

        return $book;
    }
}
