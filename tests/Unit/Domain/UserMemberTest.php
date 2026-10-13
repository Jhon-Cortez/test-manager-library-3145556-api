<?php

declare(strict_types=1);

namespace Tests\Unit\Domain;

use App\Domain\DocumentNumber;
use App\Domain\Exceptions\InvalidValue;
use App\Domain\Member;
use App\Domain\Role;
use App\Domain\User;
use PHPUnit\Framework\TestCase;

final class UserMemberTest extends TestCase
{
    public function test_username_is_stored_lowercase_and_trimmed(): void
    {
        $user = User::register('user-1', ' Ana ', 'hash-opaco', Role::Librarian);

        self::assertSame('ana', $user->username());
    }

    public function test_username_cannot_be_empty_after_normalizing(): void
    {
        $this->expectException(InvalidValue::class);

        User::register('user-1', '   ', 'hash-opaco', Role::Librarian);
    }

    public function test_password_hash_is_required(): void
    {
        $this->expectException(InvalidValue::class);

        User::register('user-1', 'ana', '  ', Role::Librarian);
    }

    public function test_member_requires_a_document(): void
    {
        $this->expectException(InvalidValue::class);

        Member::register('member-1', 'Ana Torres', DocumentNumber::of(' '));
    }

    public function test_member_requires_a_name(): void
    {
        $this->expectException(InvalidValue::class);

        Member::register('member-1', '  ', DocumentNumber::of('123456789'));
    }
}
