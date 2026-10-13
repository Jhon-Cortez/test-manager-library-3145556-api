<?php

declare(strict_types=1);

namespace App\Domain;

use App\Domain\Exceptions\DuplicateBookInLoan;
use App\Domain\Exceptions\InvalidLoanSize;
use App\Domain\Exceptions\ItemNotFound;
use DateTimeImmutable;

final class Loan
{
    private const MAX_ITEMS = 3;
    private const LOAN_DAYS = 14;

    /** @var LoanItem[] */
    private array $items = [];

    private function __construct(
        private readonly string $id,
        private readonly string $memberId,
        private readonly string $loanedByUserId,
        private readonly DateTimeImmutable $loanedAt,
    ) {
    }

    public static function register(
        string $id,
        string $memberId,
        string $loanedByUserId,
        DateTimeImmutable $loanedAt,
    ): self {
        return new self($id, $memberId, $loanedByUserId, $loanedAt);
    }

    /** @param LoanItem[] $items */
    public static function reconstitute(
        string $id,
        string $memberId,
        string $loanedByUserId,
        DateTimeImmutable $loanedAt,
        array $items,
    ): self {
        $loan = new self($id, $memberId, $loanedByUserId, $loanedAt);
        $loan->items = $items;

        return $loan;
    }

    public function addItem(string $itemId, Book $book, string $categoryName): LoanItem
    {
        if (count($this->items) >= self::MAX_ITEMS) {
            throw new InvalidLoanSize('Un préstamo admite de 1 a 3 líneas.');
        }
        foreach ($this->items as $item) {
            if ($item->bookId() === $book->id()) {
                throw new DuplicateBookInLoan('El libro ya está en este préstamo.');
            }
        }

        $book->withdraw();

        $item = LoanItem::freeze(
            $itemId,
            $this->id,
            $book,
            $categoryName,
            $this->loanedAt->modify('+' . self::LOAN_DAYS . ' days'),
        );
        $this->items[] = $item;

        return $item;
    }

    public function returnItem(string $itemId, Book $book, DateTimeImmutable $now): LoanItem
    {
        foreach ($this->items as $item) {
            if ($item->id() === $itemId) {
                $item->markReturned($now);
                $book->restock();

                return $item;
            }
        }

        throw new ItemNotFound('La línea no pertenece a este préstamo.');
    }

    public function statusAt(DateTimeImmutable $now): LoanStatus
    {
        $open = 0;
        foreach ($this->items as $item) {
            if ($item->isReturned()) {
                continue;
            }
            if ($item->isOverdueAt($now)) {
                return LoanStatus::Overdue;
            }
            $open++;
        }

        return $open === 0 ? LoanStatus::Closed : LoanStatus::Open;
    }

    /** @return LoanItem[] */
    public function items(): array
    {
        return $this->items;
    }

    public function hasItem(string $itemId): bool
    {
        foreach ($this->items as $item) {
            if ($item->id() === $itemId) {
                return true;
            }
        }

        return false;
    }

    public function id(): string
    {
        return $this->id;
    }

    public function memberId(): string
    {
        return $this->memberId;
    }

    public function loanedByUserId(): string
    {
        return $this->loanedByUserId;
    }

    public function loanedAt(): DateTimeImmutable
    {
        return $this->loanedAt;
    }
}
