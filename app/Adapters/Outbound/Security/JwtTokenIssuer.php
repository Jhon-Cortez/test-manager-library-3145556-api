<?php

declare(strict_types=1);

namespace App\Adapters\Outbound\Security;

use App\Application\Ports\Outbound\TokenIssuer;
use App\Domain\User;
use DateTimeImmutable;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

final class JwtTokenIssuer implements TokenIssuer
{
    public function __construct(private readonly string $secret)
    {
    }

    public function issue(User $user, DateTimeImmutable $expiresAt): string
    {
        $now = time();

        return JWT::encode([
            'sub' => $user->id(),
            'role' => $user->role()->value,
            'iat' => $now,
            'exp' => (int) $expiresAt->format('U'),
        ], $this->secret, 'HS256');
    }

    /** @return array<string, mixed> */
    public function decode(string $token): array
    {
        return (array) JWT::decode($token, new Key($this->secret, 'HS256'));
    }
}
