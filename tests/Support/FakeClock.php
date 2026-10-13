<?php

declare(strict_types=1);

namespace Tests\Support;

use DateTimeImmutable;

final class FakeClock
{
    private function __construct(private DateTimeImmutable $now)
    {
    }

    public static function at(string $datetime): self
    {
        return new self(new DateTimeImmutable($datetime));
    }

    public function now(): DateTimeImmutable
    {
        return $this->now;
    }

    public function advance(string $modifier): void
    {
        $this->now = $this->now->modify($modifier);
    }
}
