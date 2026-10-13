<?php

declare(strict_types=1);

namespace Tests\Fakes;

use App\Application\Ports\Outbound\TokenIssuer;
use App\Domain\User;
use DateTimeImmutable;

final class FakeTokenIssuer implements TokenIssuer
{
    /** @var array<int, array{user_id: string, expires_at: DateTimeImmutable}> */
    public array $issued = [];

    public function issue(User $user, DateTimeImmutable $expiresAt): string
    {
        $this->issued[] = ['user_id' => $user->id(), 'expires_at' => $expiresAt];

        return 'token-for-' . $user->username();
    }
}
