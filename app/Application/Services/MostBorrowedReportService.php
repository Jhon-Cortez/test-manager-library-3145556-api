<?php

declare(strict_types=1);

namespace App\Application\Services;

use App\Application\Ports\Outbound\ReportReader;
use App\Domain\DateRange;

final class MostBorrowedReportService
{
    public function __construct(private readonly ReportReader $reports)
    {
    }

    /** @return array<int, array{book_id: string, book_title: string, category_name: string, times_borrowed: int}> */
    public function mostBorrowed(DateRange $range): array
    {
        return $this->reports->mostBorrowed($range);
    }
}
