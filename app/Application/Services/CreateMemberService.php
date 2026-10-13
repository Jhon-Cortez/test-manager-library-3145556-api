<?php

declare(strict_types=1);

namespace App\Application\Services;

use App\Application\Exceptions\DocumentTaken;
use App\Application\Ports\Outbound\MemberRepository;
use App\Application\Support\Uuid;
use App\Domain\DocumentNumber;
use App\Domain\Member;

final class CreateMemberService
{
    public function __construct(private readonly MemberRepository $members)
    {
    }

    public function create(string $fullName, string $documentNumber): Member
    {
        $documentNumber = DocumentNumber::of($documentNumber);
        if ($this->members->existsByDocument($documentNumber->value())) {
            throw new DocumentTaken();
        }

        $member = Member::register(Uuid::new(), $fullName, $documentNumber);
        $this->members->add($member);

        return $member;
    }
}
