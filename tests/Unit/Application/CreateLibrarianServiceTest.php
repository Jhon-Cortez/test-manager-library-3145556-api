<?php

declare(strict_types=1);

namespace Tests\Unit\Application;

use App\Application\Exceptions\UsernameTaken;
use App\Application\Services\CreateLibrarianService;
use App\Domain\Role;
use App\Domain\User;
use PHPUnit\Framework\TestCase;
use Tests\Fakes\InMemoryUserRepository;
use Tests\Fakes\PlainPasswordHasher;

final class CreateLibrarianServiceTest extends TestCase
{
    private InMemoryUserRepository $users;
    private CreateLibrarianService $service;

    protected function setUp(): void
    {
        $this->users = new InMemoryUserRepository();
        $this->service = new CreateLibrarianService($this->users, new PlainPasswordHasher());
    }

    public function test_creates_a_librarian_with_a_normalized_username(): void
    {
        $user = $this->service->createLibrarian(' Ana ', 'secret');

        self::assertSame('ana', $user->username());
        self::assertSame(Role::Librarian, $user->role());
        self::assertSame('hashed:secret', $user->passwordHash());
        self::assertSame($user, $this->users->findByUsername('ana'));
    }

    public function test_stores_the_hash_never_the_plain_password(): void
    {
        $user = $this->service->createLibrarian('ana', 'secret');

        self::assertNotSame('secret', $user->passwordHash());
        self::assertStringContainsString('hashed:', $user->passwordHash());
    }

    public function test_duplicated_username_is_rejected(): void
    {
        $this->service->createLibrarian('ana', 'secret');

        $this->expectException(UsernameTaken::class);
        $this->service->createLibrarian('Ana', 'otra');
    }
}
