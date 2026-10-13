<?php

declare(strict_types=1);

namespace Tests\Unit\Domain;

use App\Domain\Exceptions\InvalidValue;
use App\Domain\Quantity;
use PHPUnit\Framework\TestCase;

final class QuantityTest extends TestCase
{
    public function test_zero_is_a_valid_quantity(): void
    {
        $quantity = Quantity::of(0);

        self::assertTrue($quantity->isZero());
        self::assertSame(0, $quantity->value());
    }

    public function test_rejects_negative_values(): void
    {
        $this->expectException(InvalidValue::class);

        Quantity::of(-1);
    }

    public function test_add_and_subtract(): void
    {
        $three = Quantity::of(3);

        self::assertSame(5, $three->add(Quantity::of(2))->value());
        self::assertSame(1, $three->subtract(Quantity::of(2))->value());
    }

    public function test_subtract_cannot_go_below_zero(): void
    {
        $this->expectException(InvalidValue::class);

        Quantity::of(1)->subtract(Quantity::of(2));
    }
}
