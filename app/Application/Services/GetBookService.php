<?php

declare(strict_types=1);

namespace App\Application\Services;

use App\Application\Exceptions\NotFound;
use App\Application\Ports\Outbound\BookRepository;
use App\Domain\Book;

final class GetBookService
{
    public function __construct(private readonly BookRepository $books)
    {
    }

    public function get(string $bookId): Book
    {
        $book = $this->books->findById($bookId);
        if ($book === null || $book->isDeleted()) {
            throw new NotFound('el libro');
        }

        return $book;
    }
}
