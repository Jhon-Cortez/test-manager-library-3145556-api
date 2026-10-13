<?php

declare(strict_types=1);

namespace App\Domain;

use App\Domain\Exceptions\InvalidValue;

final class Isbn
{
    private function __construct(private readonly string $value)
    {
    }

    public static function of(string $value): self
    {
        $value = trim($value);
        if (preg_match('/^[0-9]{13}$/', $value) !== 1) {
            throw new InvalidValue('El ISBN debe tener exactamente 13 dígitos.');
        }

        return new self($value);
    }

    public function value(): string
    {
        return $this->value;
    }
}
