<?php

declare(strict_types=1);

namespace App\Application\Ports\Outbound;

use App\Domain\User;
use DateTimeImmutable;

interface TokenIssuer
{
    public function issue(User $user, DateTimeImmutable $expiresAt): string;
}
