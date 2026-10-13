<?php

declare(strict_types=1);

namespace Tests\Unit\Domain;

use App\Domain\Exceptions\InvalidValue;
use App\Domain\Isbn;
use PHPUnit\Framework\TestCase;

final class IsbnTest extends TestCase
{
    public function test_accepts_exactly_thirteen_digits(): void
    {
        $isbn = Isbn::of('9780307474728');

        self::assertSame('9780307474728', $isbn->value());
    }

    public function test_trims_surrounding_whitespace(): void
    {
        self::assertSame('9780307474728', Isbn::of(' 9780307474728 ')->value());
    }

    public function test_rejects_other_lengths(): void
    {
        $this->expectException(InvalidValue::class);

        Isbn::of('978030747472');
    }

    public function test_rejects_non_digits(): void
    {
        $this->expectException(InvalidValue::class);

        Isbn::of('978030747472X');
    }
}
