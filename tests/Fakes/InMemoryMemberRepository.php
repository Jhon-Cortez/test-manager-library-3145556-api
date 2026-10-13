<?php

declare(strict_types=1);

namespace Tests\Fakes;

use App\Application\Ports\Outbound\MemberRepository;
use App\Application\Ports\Outbound\Support\Page;
use App\Domain\Member;

final class InMemoryMemberRepository implements MemberRepository
{
    /** @var array<string, Member> */
    private array $members = [];

    public function findById(string $id): ?Member
    {
        return $this->members[$id] ?? null;
    }

    public function add(Member $member): void
    {
        $this->members[$member->id()] = $member;
    }

    public function existsByDocument(string $documentNumber): bool
    {
        foreach ($this->members as $member) {
            if ($member->documentNumber()->value() === $documentNumber) {
                return true;
            }
        }

        return false;
    }

    public function search(?string $query, int $page, int $size): Page
    {
        $filtered = [];
        foreach ($this->members as $member) {
            if ($query !== null && $query !== '') {
                $needle = mb_strtolower($query);
                $inName = str_contains(mb_strtolower($member->fullName()), $needle);
                $inDocument = str_contains(mb_strtolower($member->documentNumber()->value()), $needle);
                if (! $inName && ! $inDocument) {
                    continue;
                }
            }
            $filtered[] = $member;
        }

        usort($filtered, static fn (Member $a, Member $b): int => strcmp($a->fullName(), $b->fullName()));

        return new Page(
            array_slice($filtered, ($page - 1) * $size, $size),
            count($filtered),
            $page,
            $size,
        );
    }
}
