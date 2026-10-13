<?php

declare(strict_types=1);

namespace App\Domain;

use App\Domain\Exceptions\ItemAlreadyReturned;
use DateTimeImmutable;

final class LoanItem
{
    private function __construct(
        private readonly string $id,
        private readonly string $loanId,
        private readonly string $bookId,
        private readonly string $bookTitle,
        private readonly Isbn $isbn,
        private readonly string $categoryName,
        private readonly DateTimeImmutable $dueAt,
        private ?DateTimeImmutable $returnedAt,
    ) {
    }

    public static function freeze(
        string $id,
        string $loanId,
        Book $book,
        string $categoryName,
        DateTimeImmutable $dueAt,
    ): self {
        return new self(
            $id,
            $loanId,
            $book->id(),
            $book->title(),
            $book->isbn(),
            $categoryName,
            $dueAt,
            null,
        );
    }

    public function markReturned(DateTimeImmutable $now): void
    {
        if ($this->returnedAt !== null) {
            throw new ItemAlreadyReturned('La línea ya fue devuelta.');
        }

        $this->returnedAt = $now;
    }

    public function isReturned(): bool
    {
        return $this->returnedAt !== null;
    }

    public function isOverdueAt(DateTimeImmutable $now): bool
    {
        return $this->returnedAt === null && $this->dueAt < $now;
    }

    public function daysLateAt(DateTimeImmutable $now): int
    {
        if (! $this->isOverdueAt($now)) {
            return 0;
        }

        $secondsLate = ((float) $now->format('U.u')) - ((float) $this->dueAt->format('U.u'));

        return (int) floor($secondsLate / 86400.0);
    }

    public function id(): string
    {
        return $this->id;
    }

    public function loanId(): string
    {
        return $this->loanId;
    }

    public function bookId(): string
    {
        return $this->bookId;
    }

    public function bookTitle(): string
    {
        return $this->bookTitle;
    }

    public function isbn(): Isbn
    {
        return $this->isbn;
    }

    public function categoryName(): string
    {
        return $this->categoryName;
    }

    public function dueAt(): DateTimeImmutable
    {
        return $this->dueAt;
    }

    public function returnedAt(): ?DateTimeImmutable
    {
        return $this->returnedAt;
    }
}
