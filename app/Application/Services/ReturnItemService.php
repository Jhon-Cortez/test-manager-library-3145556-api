<?php

declare(strict_types=1);

namespace App\Application\Services;

use App\Application\Exceptions\NotFound;
use App\Application\Ports\Outbound\BookRepository;
use App\Application\Ports\Outbound\Clock;
use App\Application\Ports\Outbound\LoanRepository;
use App\Domain\LoanItem;

final class ReturnItemService
{
    public function __construct(
        private readonly LoanRepository $loans,
        private readonly BookRepository $books,
        private readonly Clock $clock,
    ) {
    }

    public function returnItem(string $loanId, string $itemId): LoanItem
    {
        $loan = $this->loans->findById($loanId);
        if ($loan === null) {
            throw new NotFound('el préstamo');
        }

        $item = null;
        foreach ($loan->items() as $candidate) {
            if ($candidate->id() === $itemId) {
                $item = $candidate;
                break;
            }
        }
        if ($item === null) {
            throw new NotFound('la línea');
        }

        $book = $this->books->findById($item->bookId());
        if ($book === null) {
            throw new NotFound('el libro');
        }

        $returned = $loan->returnItem($itemId, $book, $this->clock->now());
        $this->loans->save($loan);
        $this->books->save($book);

        return $returned;
    }
}
