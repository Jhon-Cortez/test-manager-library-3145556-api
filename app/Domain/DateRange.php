<?php

declare(strict_types=1);

namespace App\Domain;

use App\Domain\Exceptions\InvalidValue;
use DateTimeImmutable;

final class DateRange
{
    private function __construct(
        private readonly DateTimeImmutable $start,
        private readonly DateTimeImmutable $end,
    ) {
    }

    public static function of(DateTimeImmutable $start, DateTimeImmutable $end): self
    {
        if ($end < $start) {
            throw new InvalidValue('El fin del rango no puede ser anterior al inicio.');
        }

        return new self($start, $end);
    }

    public function start(): DateTimeImmutable
    {
        return $this->start;
    }

    public function end(): DateTimeImmutable
    {
        return $this->end;
    }
}
