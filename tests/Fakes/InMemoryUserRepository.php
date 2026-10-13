<?php

declare(strict_types=1);

namespace Tests\Fakes;

use App\Application\Ports\Outbound\UserRepository;
use App\Domain\User;

final class InMemoryUserRepository implements UserRepository
{
    /** @var array<string, User> */
    private array $byUsername = [];

    /** @var array<string, User> */
    private array $byId = [];

    public function findById(string $id): ?User
    {
        return $this->byId[$id] ?? null;
    }

    public function findByUsername(string $username): ?User
    {
        return $this->byUsername[$username] ?? null;
    }

    public function existsByUsername(string $username): bool
    {
        return isset($this->byUsername[$username]);
    }

    public function add(User $user): void
    {
        $this->byUsername[$user->username()] = $user;
        $this->byId[$user->id()] = $user;
    }
}
