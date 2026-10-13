<?php

declare(strict_types=1);

namespace App\Application\Ports\Outbound;

use App\Application\Ports\Outbound\Support\Page;
use App\Domain\DateRange;
use App\Domain\Loan;
use App\Domain\LoanStatus;
use DateTimeImmutable;

interface LoanRepository
{
    public function findById(string $id): ?Loan;

    public function add(Loan $loan): void;

    public function save(Loan $loan): void;

    public function countUnreturnedItemsByMember(string $memberId): int;

    public function hasOverdueItemsByMember(string $memberId, DateTimeImmutable $now): bool;

    /** @param string[] $bookIds @return string[] book ids that still hold unreturned lines */
    public function bookIdsWithUnreturnedItems(array $bookIds): array;

    public function search(?DateRange $range, ?LoanStatus $status, ?string $memberId, int $page, int $size, DateTimeImmutable $now): Page;

    /** @return array<int, array{loan_id: string, item_id: string, book_title: string, member_id: string, due_at: DateTimeImmutable, days_late: int}> */
    public function overdueItems(DateTimeImmutable $now): array;
}
