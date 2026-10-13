<?php

declare(strict_types=1);

namespace Tests\Fakes;

use App\Application\Ports\Outbound\PasswordHasher;

final class PlainPasswordHasher implements PasswordHasher
{
    public function hash(string $plain): string
    {
        return 'hashed:' . $plain;
    }

    public function verify(string $plain, string $hash): bool
    {
        return $hash === $this->hash($plain);
    }
}
