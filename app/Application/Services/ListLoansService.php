<?php

declare(strict_types=1);

namespace App\Application\Services;

use App\Application\Ports\Outbound\Clock;
use App\Application\Ports\Outbound\LoanRepository;
use App\Application\Ports\Outbound\Support\Page;
use App\Domain\DateRange;
use App\Domain\LoanStatus;

final class ListLoansService
{
    public const MAX_PAGE_SIZE = 50;

    public function __construct(
        private readonly LoanRepository $loans,
        private readonly Clock $clock,
    ) {
    }

    public function list(
        ?DateRange $range,
        ?LoanStatus $status,
        ?string $memberId,
        int $page = 1,
        int $size = 20,
    ): Page {
        $page = max(1, $page);
        $size = min(max(1, $size), self::MAX_PAGE_SIZE);

        return $this->loans->search($range, $status, $memberId, $page, $size, $this->clock->now());
    }
}
