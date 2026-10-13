<?php

declare(strict_types=1);

namespace Tests\Integration\Persistence;

use App\Adapters\Outbound\Persistence\EloquentMemberRepository;
use App\Adapters\Outbound\Persistence\EloquentUserRepository;
use App\Domain\DocumentNumber;
use App\Domain\Member;
use App\Domain\Role;
use App\Domain\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

final class MemberUserRepositoryTest extends TestCase
{
    use DatabaseTransactions;

    public function test_member_round_trips_and_detects_duplicated_documents(): void
    {
        $members = new EloquentMemberRepository();
        $member = Member::register('77777777-aaaa-4aaa-8aaa-777777777777', 'Ana Torres', DocumentNumber::of('123456789'));
        $members->add($member);

        $found = $members->findById($member->id());
        self::assertNotNull($found);
        self::assertSame('Ana Torres', $found->fullName());
        self::assertSame('123456789', $found->documentNumber()->value());

        self::assertTrue($members->existsByDocument('123456789'));
        self::assertFalse($members->existsByDocument('000000000'));

        $page = $members->search('torres', 1, 10);
        self::assertSame(1, $page->total());
    }

    public function test_user_round_trips_with_normalized_username(): void
    {
        $users = new EloquentUserRepository();
        $user = User::register('88888888-aaaa-4aaa-8aaa-888888888888', ' Ana ', 'hashed:secret', Role::Librarian);
        $users->add($user);

        $found = $users->findByUsername('ana');
        self::assertNotNull($found);
        self::assertSame('ana', $found->username());
        self::assertSame('hashed:secret', $found->passwordHash());
        self::assertSame(Role::Librarian, $found->role());

        self::assertTrue($users->existsByUsername('ana'));
        self::assertFalse($users->existsByUsername('nadie'));
    }
}
