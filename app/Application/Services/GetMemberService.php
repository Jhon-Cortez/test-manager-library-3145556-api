<?php

declare(strict_types=1);

namespace App\Application\Services;

use App\Application\Exceptions\NotFound;
use App\Application\Ports\Outbound\LoanRepository;
use App\Application\Ports\Outbound\MemberRepository;
use App\Domain\Member;

final class GetMemberService
{
    public function __construct(
        private readonly MemberRepository $members,
        private readonly LoanRepository $loans,
    ) {
    }

    /** @return array{member: Member, active_loans: int} */
    public function get(string $memberId): array
    {
        $member = $this->members->findById($memberId);
        if ($member === null) {
            throw new NotFound('el socio');
        }

        return [
            'member' => $member,
            'active_loans' => $this->loans->countUnreturnedItemsByMember($member->id()),
        ];
    }
}
