<?php

declare(strict_types=1);

namespace App\Application\Services;

use App\Application\Exceptions\IsbnTaken;
use App\Application\Ports\Outbound\BookRepository;
use App\Application\Support\Uuid;
use App\Domain\Book;
use App\Domain\Isbn;
use App\Domain\Quantity;

final class CreateBookService
{
    public function __construct(private readonly BookRepository $books)
    {
    }

    public function create(
        string $title,
        string $author,
        string $isbn,
        string $categoryId,
        int $totalCopies,
    ): Book {
        $isbn = Isbn::of($isbn);
        if ($this->books->existsByIsbn($isbn->value())) {
            throw new IsbnTaken();
        }

        $book = Book::create(
            Uuid::new(),
            $title,
            $author,
            $isbn,
            $categoryId,
            Quantity::of($totalCopies),
        );
        $this->books->add($book);

        return $book;
    }
}
