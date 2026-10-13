<?php

declare(strict_types=1);

namespace App\Domain;

use App\Domain\Exceptions\InvalidValue;

final class Quantity
{
    private function __construct(private readonly int $value)
    {
    }

    public static function of(int $value): self
    {
        if ($value < 0) {
            throw new InvalidValue('La cantidad no puede ser negativa.');
        }

        return new self($value);
    }

    public function value(): int
    {
        return $this->value;
    }

    public function add(Quantity $other): self
    {
        return new self($this->value + $other->value);
    }

    public function subtract(Quantity $other): self
    {
        $result = $this->value - $other->value;
        if ($result < 0) {
            throw new InvalidValue('La cantidad no puede ser negativa.');
        }

        return new self($result);
    }

    public function isZero(): bool
    {
        return $this->value === 0;
    }
}
