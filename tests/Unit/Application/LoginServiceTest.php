<?php

declare(strict_types=1);

namespace Tests\Unit\Application;

use App\Application\Exceptions\InvalidCredentials;
use App\Application\Services\LoginService;
use App\Domain\Role;
use App\Domain\User;
use PHPUnit\Framework\TestCase;
use Tests\Fakes\FakeTokenIssuer;
use Tests\Fakes\InMemoryUserRepository;
use Tests\Fakes\PlainPasswordHasher;
use Tests\Support\FakeClock;

final class LoginServiceTest extends TestCase
{
    private InMemoryUserRepository $users;
    private FakeTokenIssuer $tokens;
    private FakeClock $clock;
    private LoginService $service;

    protected function setUp(): void
    {
        $this->users = new InMemoryUserRepository();
        $this->tokens = new FakeTokenIssuer();
        $this->clock = FakeClock::at('2026-10-13T09:00:00Z');
        $this->service = new LoginService(
            $this->users,
            new PlainPasswordHasher(),
            $this->tokens,
            $this->clock,
        );
    }

    public function test_valid_credentials_return_an_eight_hour_token(): void
    {
        $this->users->add(User::register('user-1', 'ana', 'hashed:secret', Role::Librarian));

        $result = $this->service->login('ana', 'secret');

        self::assertSame('token-for-ana', $result['access_token']);
        self::assertSame('Bearer', $result['token_type']);
        self::assertSame(28800, $result['expires_in']);
        self::assertSame('librarian', $result['role']);
        self::assertSame('2026-10-13T17:00:00+00:00', $this->tokens->issued[0]['expires_at']->format(DATE_ATOM));
    }

    public function test_unknown_user_is_rejected_without_revealing_the_reason(): void
    {
        try {
            $this->service->login('nadie', 'secret');
            self::fail('Se esperaba InvalidCredentials');
        } catch (InvalidCredentials $e) {
            self::assertStringNotContainsString('nadie', $e->getMessage());
            self::assertStringNotContainsString('secret', $e->getMessage());
            self::assertStringNotContainsString('clave', $e->getMessage());
        }
    }

    public function test_wrong_password_is_rejected_without_revealing_the_reason(): void
    {
        $this->users->add(User::register('user-1', 'ana', 'hashed:secret', Role::Librarian));

        try {
            $this->service->login('ana', 'otra');
            self::fail('Se esperaba InvalidCredentials');
        } catch (InvalidCredentials $e) {
            self::assertStringNotContainsString('ana', $e->getMessage());
            self::assertStringNotContainsString('otra', $e->getMessage());
            self::assertStringNotContainsString('clave', $e->getMessage());
        }
    }

    public function test_username_lookup_normalizes_the_input(): void
    {
        $this->users->add(User::register('user-1', 'ana', 'hashed:secret', Role::Librarian));

        $result = $this->service->login(' Ana ', 'secret');

        self::assertSame('librarian', $result['role']);
    }
}
