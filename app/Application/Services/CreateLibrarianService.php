<?php

declare(strict_types=1);

namespace App\Application\Services;

use App\Application\Exceptions\UsernameTaken;
use App\Application\Ports\Outbound\PasswordHasher;
use App\Application\Ports\Outbound\UserRepository;
use App\Application\Support\Uuid;
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
            Uuid::new(),
            $username,
            $this->hasher->hash($password),
            Role::Librarian,
        );
        $this->users->add($user);

        return $user;
    }

}
