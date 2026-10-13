<?php

declare(strict_types=1);

namespace App\Adapters\Outbound\Persistence;

use App\Application\Ports\Outbound\ReportReader;
use App\Domain\DateRange;
use Illuminate\Support\Facades\DB;

final class EloquentReportReader implements ReportReader
{
    public function mostBorrowed(DateRange $range): array
    {
        $rows = DB::table('library.loan_item')
            ->join('library.loan', 'library.loan.id', '=', 'library.loan_item.loan_id')
            ->whereBetween('library.loan.loaned_at', [
                $range->start()->format('Y-m-d H:i:s.uP'),
                $range->end()->format('Y-m-d H:i:s.uP'),
            ])
            ->groupBy('library.loan_item.book_id', 'library.loan_item.book_title', 'library.loan_item.category_name')
            ->orderByDesc('times_borrowed')
            ->orderBy('library.loan_item.book_title')
            ->get([
                'library.loan_item.book_id',
                'library.loan_item.book_title',
                'library.loan_item.category_name',
                DB::raw('COUNT(*) AS times_borrowed'),
            ]);

        $result = [];
        foreach ($rows as $row) {
            $result[] = [
                'book_id' => (string) $row->book_id,
                'book_title' => (string) $row->book_title,
                'category_name' => (string) $row->category_name,
                'times_borrowed' => (int) $row->times_borrowed,
            ];
        }

        return $result;
    }
}
