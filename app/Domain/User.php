<?php

declare(strict_types=1);

namespace App\Domain;

use App\Domain\Exceptions\InvalidValue;

final class User
{
    private function __construct(
        private readonly string $id,
        private readonly string $username,
        private readonly string $passwordHash,
        private readonly Role $role,
    ) {
    }

    public static function register(string $id, string $username, string $passwordHash, Role $role): self
    {
        $username = self::normalize($username);
        if ($username === '') {
            throw new InvalidValue('El nombre de usuario no puede estar vacío.');
        }
        $passwordHash = trim($passwordHash);
        if ($passwordHash === '') {
            throw new InvalidValue('El hash de la clave no puede estar vacío.');
        }

        return new self($id, $username, $passwordHash, $role);
    }

    public static function reconstitute(string $id, string $username, string $passwordHash, Role $role): self
    {
        return new self($id, $username, $passwordHash, $role);
    }

    public static function normalize(string $username): string
    {
        return strtolower(trim($username));
    }

    public function id(): string
    {
        return $this->id;
    }

    public function username(): string
    {
        return $this->username;
    }

    public function passwordHash(): string
    {
        return $this->passwordHash;
    }

    public function role(): Role
    {
        return $this->role;
    }
}
