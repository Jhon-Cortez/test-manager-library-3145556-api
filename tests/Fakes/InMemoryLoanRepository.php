<?php

declare(strict_types=1);

namespace Tests\Fakes;

use App\Application\Ports\Outbound\LoanRepository;
use App\Application\Ports\Outbound\Support\Page;
use App\Domain\DateRange;
use App\Domain\Loan;
use App\Domain\LoanStatus;
use DateTimeImmutable;

final class InMemoryLoanRepository implements LoanRepository
{
    /** @var array<string, Loan> */
    private array $loans = [];

    public function findById(string $id): ?Loan
    {
        return $this->loans[$id] ?? null;
    }

    public function count(): int
    {
        return count($this->loans);
    }

    public function add(Loan $loan): void
    {
        $this->loans[$loan->id()] = $loan;
    }

    public function save(Loan $loan): void
    {
        $this->loans[$loan->id()] = $loan;
    }

    public function countUnreturnedItemsByMember(string $memberId): int
    {
        $count = 0;
        foreach ($this->loans as $loan) {
            if ($loan->memberId() !== $memberId) {
                continue;
            }
            foreach ($loan->items() as $item) {
                if (! $item->isReturned()) {
                    $count++;
                }
            }
        }

        return $count;
    }

    public function hasOverdueItemsByMember(string $memberId, DateTimeImmutable $now): bool
    {
        foreach ($this->loans as $loan) {
            if ($loan->memberId() !== $memberId) {
                continue;
            }
            foreach ($loan->items() as $item) {
                if ($item->isOverdueAt($now)) {
                    return true;
                }
            }
        }

        return false;
    }

    public function bookIdsWithUnreturnedItems(array $bookIds): array
    {
        $found = [];
        foreach ($this->loans as $loan) {
            foreach ($loan->items() as $item) {
                if ($item->isReturned() || ! in_array($item->bookId(), $bookIds, true)) {
                    continue;
                }
                $found[$item->bookId()] = true;
            }
        }

        return array_keys($found);
    }

    public function search(?DateRange $range, ?LoanStatus $status, ?string $memberId, int $page, int $size): Page
    {
        $filtered = [];
        foreach ($this->loans as $loan) {
            if ($memberId !== null && $loan->memberId() !== $memberId) {
                continue;
            }
            if ($range !== null && ($loan->loanedAt() < $range->start() || $loan->loanedAt() > $range->end())) {
                continue;
            }
            if ($status !== null && $this->statusOf($loan) !== $status) {
                continue;
            }
            $filtered[] = $loan;
        }

        usort(
            $filtered,
            static fn (Loan $a, Loan $b): int => $b->loanedAt() <=> $a->loanedAt(),
        );

        return new Page(
            array_slice($filtered, ($page - 1) * $size, $size),
            count($filtered),
            $page,
            $size,
        );
    }

    public function overdueItems(DateTimeImmutable $now): array
    {
        $rows = [];
        foreach ($this->loans as $loan) {
            foreach ($loan->items() as $item) {
                if (! $item->isOverdueAt($now)) {
                    continue;
                }
                $rows[] = [
                    'loan_id' => $loan->id(),
                    'item_id' => $item->id(),
                    'book_title' => $item->bookTitle(),
                    'member_name' => $loan->memberId(),
                    'due_at' => $item->dueAt(),
                    'days_late' => $item->daysLateAt($now),
                ];
            }
        }

        usort($rows, static fn (array $a, array $b): int => $b['days_late'] <=> $a['days_late']);

        return $rows;
    }

    private function statusOf(Loan $loan): LoanStatus
    {
        return $loan->statusAt(new DateTimeImmutable());
    }
}
