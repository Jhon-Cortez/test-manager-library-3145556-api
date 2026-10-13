<?php

declare(strict_types=1);

namespace App\Application\Services;

use App\Application\Exceptions\UsernameTaken;
use App\Application\Ports\Outbound\PasswordHasher;
use App\Application\Ports\Outbound\UserRepository;
use App\Domain\Role;
use App\Domain\User;

final class CreateLibrarianService
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly PasswordHasher $hasher,
    ) {
    }

    public function createLibrarian(string $username, string $password): User
    {
        $username = User::normalize($username);
        if ($this->users->existsByUsername($username)) {
            throw new UsernameTaken();
        }

        $user = User::register(
            self::newId(),
            $username,
            $this->hasher->hash($password),
            Role::Librarian,
        );
        $this->users->add($user);

        return $user;
    }

    private static function newId(): string
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4));
    }
}
