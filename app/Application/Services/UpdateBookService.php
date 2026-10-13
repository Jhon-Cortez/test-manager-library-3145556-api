<?php

declare(strict_types=1);

namespace App\Application\Services;

use App\Application\Exceptions\NotFound;
use App\Application\Ports\Outbound\BookRepository;
use App\Domain\Book;
use App\Domain\Quantity;

final class UpdateBookService
{
    public function __construct(private readonly BookRepository $books)
    {
    }

    public function update(
        string $bookId,
        string $title,
        string $author,
        string $categoryId,
        int $totalCopies,
    ): Book {
        $book = $this->books->findById($bookId);
        if ($book === null || $book->isDeleted()) {
            throw new NotFound('el libro');
        }

        $book->update($title, $author, $categoryId, Quantity::of($totalCopies));
        $this->books->save($book);

        return $book;
    }
}
