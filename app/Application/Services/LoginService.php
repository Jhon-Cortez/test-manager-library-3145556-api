<?php

declare(strict_types=1);

namespace App\Application\Services;

use App\Application\Exceptions\InvalidCredentials;
use App\Application\Ports\Outbound\Clock;
use App\Application\Ports\Outbound\PasswordHasher;
use App\Application\Ports\Outbound\TokenIssuer;
use App\Application\Ports\Outbound\UserRepository;
use App\Domain\User;

final class LoginService
{
    public const TOKEN_TTL_SECONDS = 28800;

    public function __construct(
        private readonly UserRepository $users,
        private readonly PasswordHasher $hasher,
        private readonly TokenIssuer $tokens,
        private readonly Clock $clock,
    ) {
    }

    /** @return array{access_token: string, token_type: string, expires_in: int, role: string} */
    public function login(string $username, string $password): array
    {
        $user = $this->users->findByUsername(User::normalize($username));
        if ($user === null || ! $this->hasher->verify($password, $user->passwordHash())) {
            throw new InvalidCredentials();
        }

        $expiresAt = $this->clock->now()->modify('+' . self::TOKEN_TTL_SECONDS . ' seconds');

        return [
            'access_token' => $this->tokens->issue($user, $expiresAt),
            'token_type' => 'Bearer',
            'expires_in' => self::TOKEN_TTL_SECONDS,
            'role' => $user->role()->value,
        ];
    }
}
