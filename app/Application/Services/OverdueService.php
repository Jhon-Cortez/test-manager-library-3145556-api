<?php

declare(strict_types=1);

namespace App\Application\Services;

use App\Application\Ports\Outbound\Clock;
use App\Application\Ports\Outbound\LoanRepository;
use App\Application\Ports\Outbound\MemberRepository;
use DateTimeImmutable;

final class OverdueService
{
    public function __construct(
        private readonly LoanRepository $loans,
        private readonly MemberRepository $members,
        private readonly Clock $clock,
    ) {
    }

    /** @return array<int, array{loan_id: string, item_id: string, book_title: string, member_name: string, due_at: DateTimeImmutable, days_late: int}> */
    public function overdue(): array
    {
        $rows = [];
        foreach ($this->loans->overdueItems($this->clock->now()) as $row) {
            $member = $this->members->findById($row['member_id']);
            $rows[] = [
                'loan_id' => $row['loan_id'],
                'item_id' => $row['item_id'],
                'book_title' => $row['book_title'],
                'member_name' => $member->fullName(),
                'due_at' => $row['due_at'],
                'days_late' => $row['days_late'],
            ];
        }

        return $rows;
    }
}
