<?php

declare(strict_types=1);

namespace App\Domain;

use App\Domain\Exceptions\InvalidValue;

final class Member
{
    private function __construct(
        private readonly string $id,
        private string $fullName,
        private readonly DocumentNumber $documentNumber,
    ) {
    }

    public static function register(string $id, string $fullName, DocumentNumber $documentNumber): self
    {
        $fullName = trim($fullName);
        if ($fullName === '') {
            throw new InvalidValue('El nombre del socio no puede estar vacío.');
        }

        return new self($id, $fullName, $documentNumber);
    }

    public function id(): string
    {
        return $this->id;
    }

    public function fullName(): string
    {
        return $this->fullName;
    }

    public function documentNumber(): DocumentNumber
    {
        return $this->documentNumber;
    }
}
