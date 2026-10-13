<?php

declare(strict_types=1);

namespace App\Adapters\Outbound\Security;

use App\Application\Ports\Outbound\PasswordHasher;

final class BcryptPasswordHasher implements PasswordHasher
{
    private const COST = 12;

    public function hash(string $plain): string
    {
        return password_hash($plain, PASSWORD_BCRYPT, ['cost' => self::COST]);
    }

    public function verify(string $plain, string $hash): bool
    {
        return password_verify($plain, $hash);
    }
}
