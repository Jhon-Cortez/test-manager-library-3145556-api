<?php

declare(strict_types=1);

namespace Tests\Unit\Domain;

use App\Domain\DateRange;
use App\Domain\Exceptions\InvalidValue;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class DateRangeTest extends TestCase
{
    public function test_accepts_end_equal_to_start(): void
    {
        $day = new DateTimeImmutable('2026-10-01T00:00:00Z');
        $range = DateRange::of($day, $day);

        self::assertSame($day, $range->start());
        self::assertSame($day, $range->end());
    }

    public function test_rejects_end_before_start(): void
    {
        $this->expectException(InvalidValue::class);

        DateRange::of(
            new DateTimeImmutable('2026-10-31T00:00:00Z'),
            new DateTimeImmutable('2026-10-01T00:00:00Z'),
        );
    }
}
