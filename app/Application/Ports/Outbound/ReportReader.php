<?php

declare(strict_types=1);

namespace App\Application\Ports\Outbound;

use App\Domain\DateRange;

interface ReportReader
{
    /**
     * Una fila por libro y nombre de categoría congelados (ADR-004).
     *
     * @return array<int, array{book_id: string, book_title: string, category_name: string, times_borrowed: int}>
     */
    public function mostBorrowed(DateRange $range): array;
}
