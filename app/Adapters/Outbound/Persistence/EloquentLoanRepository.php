<?php

declare(strict_types=1);

namespace App\Adapters\Outbound\Persistence;

use App\Adapters\Outbound\Persistence\Models\LoanItemModel;
use App\Adapters\Outbound\Persistence\Models\LoanModel;
use App\Application\Ports\Outbound\LoanRepository;
use App\Application\Ports\Outbound\Support\Page;
use App\Domain\DateRange;
use App\Domain\Isbn;
use App\Domain\Loan;
use App\Domain\LoanItem;
use App\Domain\LoanStatus;
use DateTimeImmutable;

final class EloquentLoanRepository implements LoanRepository
{
    public function findById(string $id): ?Loan
    {
        $row = LoanModel::query()->find($id);
        if ($row === null) {
            return null;
        }

        return $this->toDomain($row, $this->itemsOf($id));
    }

    public function add(Loan $loan): void
    {
        LoanModel::query()->insert([
            'id' => $loan->id(),
            'member_id' => $loan->memberId(),
            'loaned_by_user_id' => $loan->loanedByUserId(),
            'loaned_at' => $loan->loanedAt()->format('Y-m-d H:i:s.uP'),
        ]);

        foreach ($loan->items() as $item) {
            LoanItemModel::query()->insert($this->itemRow($item));
        }
    }

    public function save(Loan $loan): void
    {
        foreach ($loan->items() as $item) {
            if ($item->returnedAt() === null) {
                continue;
            }
            $affected = LoanItemModel::query()
                ->where('id', $item->id())
                ->where(function ($query) use ($item): void {
                    $query->whereNull('returned_at')
                        ->orWhere('returned_at', $item->returnedAt()->format('Y-m-d H:i:s.uP'));
                })
                ->update(['returned_at' => $item->returnedAt()->format('Y-m-d H:i:s.uP')]);

            if ($affected === 0) {
                throw new \App\Domain\Exceptions\ItemAlreadyReturned('La línea ya fue devuelta.');
            }
        }
    }

    public function countUnreturnedItemsByMember(string $memberId): int
    {
        return LoanItemModel::query()
            ->whereNull('returned_at')
            ->whereIn('loan_id', function ($query) use ($memberId): void {
                $query->select('id')->from('library.loan')->where('member_id', $memberId);
            })
            ->count();
    }

    public function hasOverdueItemsByMember(string $memberId, DateTimeImmutable $now): bool
    {
        return LoanItemModel::query()
            ->whereNull('returned_at')
            ->where('due_at', '<', $now->format('Y-m-d H:i:s.uP'))
            ->whereIn('loan_id', function ($query) use ($memberId): void {
                $query->select('id')->from('library.loan')->where('member_id', $memberId);
            })
            ->exists();
    }

    public function bookIdsWithUnreturnedItems(array $bookIds): array
    {
        if ($bookIds === []) {
            return [];
        }

        return LoanItemModel::query()
            ->whereNull('returned_at')
            ->whereIn('book_id', $bookIds)
            ->pluck('book_id')
            ->map(fn ($id): string => (string) $id)
            ->unique()
            ->values()
            ->all();
    }

    public function search(?DateRange $range, ?LoanStatus $status, ?string $memberId, int $page, int $size, DateTimeImmutable $now): Page
    {
        $builder = LoanModel::query()->from('library.loan');
        if ($memberId !== null) {
            $builder->where('member_id', $memberId);
        }
        if ($range !== null) {
            $builder->whereBetween('loaned_at', [
                $range->start()->format('Y-m-d H:i:s.uP'),
                $range->end()->format('Y-m-d H:i:s.uP'),
            ]);
        }

        $unreturned = fn ($query) => $query->selectRaw('1')
            ->from('library.loan_item')
            ->whereColumn('library.loan_item.loan_id', 'library.loan.id')
            ->whereNull('library.loan_item.returned_at');
        $overdue = fn ($query) => $query->selectRaw('1')
            ->from('library.loan_item')
            ->whereColumn('library.loan_item.loan_id', 'library.loan.id')
            ->whereNull('library.loan_item.returned_at')
            ->where('library.loan_item.due_at', '<', $now->format('Y-m-d H:i:s.uP'));

        if ($status === LoanStatus::Closed) {
            $builder->whereNotExists($unreturned);
        } elseif ($status === LoanStatus::Overdue) {
            $builder->whereExists($overdue);
        } elseif ($status === LoanStatus::Open) {
            $builder->whereExists($unreturned)->whereNotExists($overdue);
        }

        $total = (clone $builder)->count();
        $rows = $builder->orderByDesc('loaned_at')
            ->offset(($page - 1) * $size)
            ->limit($size)
            ->get();

        $items = $rows->map(function (LoanModel $row): Loan {
            return $this->toDomain($row, $this->itemsOf((string) $row->id));
        })->all();

        return new Page($items, $total, $page, $size);
    }

    public function overdueItems(DateTimeImmutable $now): array
    {
        $rows = LoanItemModel::query()
            ->from('library.loan_item')
            ->join('library.loan', 'library.loan.id', '=', 'library.loan_item.loan_id')
            ->join('library.member', 'library.member.id', '=', 'library.loan.member_id')
            ->whereNull('library.loan_item.returned_at')
            ->where('library.loan_item.due_at', '<', $now->format('Y-m-d H:i:s.uP'))
            ->orderByDesc('library.loan_item.due_at')
            ->get([
                'library.loan_item.id as item_id',
                'library.loan_item.loan_id as loan_id',
                'library.loan_item.book_title as book_title',
                'library.loan_item.due_at as due_at',
                'library.member.id as member_id',
            ]);

        $result = [];
        foreach ($rows as $row) {
            $dueAt = new DateTimeImmutable((string) $row->due_at);
            $secondsLate = ((float) $now->format('U.u')) - ((float) $dueAt->format('U.u'));
            $result[] = [
                'loan_id' => (string) $row->loan_id,
                'item_id' => (string) $row->item_id,
                'book_title' => (string) $row->book_title,
                'member_id' => (string) $row->member_id,
                'due_at' => $dueAt,
                'days_late' => (int) floor($secondsLate / 86400.0),
            ];
        }

        usort($result, static fn (array $a, array $b): int => $b['days_late'] <=> $a['days_late']);

        return $result;
    }

    /** @return LoanItem[] */
    private function itemsOf(string $loanId): array
    {
        return LoanItemModel::query()->where('loan_id', $loanId)->get()
            ->map(fn (LoanItemModel $row): LoanItem => $this->itemToDomain($row))
            ->all();
    }

    private function toDomain(LoanModel $row, array $items): Loan
    {
        return Loan::reconstitute(
            (string) $row->id,
            (string) $row->member_id,
            (string) $row->loaned_by_user_id,
            new DateTimeImmutable((string) $row->loaned_at),
            $items,
        );
    }

    private function itemToDomain(LoanItemModel $row): LoanItem
    {
        return LoanItem::reconstitute(
            (string) $row->id,
            (string) $row->loan_id,
            (string) $row->book_id,
            (string) $row->book_title,
            Isbn::of((string) $row->isbn),
            (string) $row->category_name,
            new DateTimeImmutable((string) $row->due_at),
            $row->returned_at === null ? null : new DateTimeImmutable((string) $row->returned_at),
        );
    }

    /** @return array<string, mixed> */
    private function itemRow(LoanItem $item): array
    {
        return [
            'id' => $item->id(),
            'loan_id' => $item->loanId(),
            'book_id' => $item->bookId(),
            'book_title' => $item->bookTitle(),
            'isbn' => $item->isbn()->value(),
            'category_name' => $item->categoryName(),
            'due_at' => $item->dueAt()->format('Y-m-d H:i:s.uP'),
            'returned_at' => $item->returnedAt()?->format('Y-m-d H:i:s.uP'),
        ];
    }
}
