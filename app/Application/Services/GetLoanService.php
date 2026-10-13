<?php

declare(strict_types=1);

namespace App\Application\Services;

use App\Application\Exceptions\NotFound;
use App\Application\Ports\Outbound\LoanRepository;
use App\Domain\Loan;

final class GetLoanService
{
    public function __construct(private readonly LoanRepository $loans)
    {
    }

    public function get(string $loanId): Loan
    {
        $loan = $this->loans->findById($loanId);
        if ($loan === null) {
            throw new NotFound('el préstamo');
        }

        return $loan;
    }
}
