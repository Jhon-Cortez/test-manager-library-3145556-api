<?php

declare(strict_types=1);

namespace Tests\Fakes;

use App\Application\Ports\Outbound\ReportReader;
use App\Domain\DateRange;

final class InMemoryReportReader implements ReportReader
{
    public function __construct(private readonly InMemoryLoanRepository $loans)
    {
    }

    public function mostBorrowed(DateRange $range): array
    {
        $groups = [];
        foreach ($this->loans->all() as $loan) {
            if ($loan->loanedAt() < $range->start() || $loan->loanedAt() > $range->end()) {
                continue;
            }
            foreach ($loan->items() as $item) {
                $key = $item->bookId() . '|' . $item->bookTitle() . '|' . $item->categoryName();
                $groups[$key] ??= [
                    'book_id' => $item->bookId(),
                    'book_title' => $item->bookTitle(),
                    'category_name' => $item->categoryName(),
                    'times_borrowed' => 0,
                ];
                $groups[$key]['times_borrowed']++;
            }
        }

        $rows = array_values($groups);
        usort($rows, static function (array $a, array $b): int {
            return [$b['times_borrowed'], $a['book_title']] <=> [$a['times_borrowed'], $b['book_title']];
        });

        return $rows;
    }
}
