<?php

declare(strict_types=1);

namespace App\Adapters\Outbound\Security;

use App\Application\Ports\Outbound\TokenIssuer;
use App\Application\Ports\Outbound\TokenParser;
use App\Domain\User;
use DateTimeImmutable;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

final class JwtTokenIssuer implements TokenIssuer, TokenParser
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

    public function parse(string $token): array
    {
        $claims = (array) JWT::decode($token, new Key($this->secret, 'HS256'));

        return [
            'sub' => (string) $claims['sub'],
            'role' => (string) $claims['role'],
        ];
    }
}
