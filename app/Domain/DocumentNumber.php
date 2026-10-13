<?php

declare(strict_types=1);

namespace App\Domain;

use App\Domain\Exceptions\InvalidValue;

final class DocumentNumber
{
    private function __construct(private readonly string $value)
    {
    }

    public static function of(string $value): self
    {
        $value = trim($value);
        if ($value === '') {
            throw new InvalidValue('El documento no puede estar vacío.');
        }

        return new self($value);
    }

    public function value(): string
    {
        return $this->value;
    }
}
