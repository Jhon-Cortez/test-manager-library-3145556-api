<?php

declare(strict_types=1);

namespace App\Adapters\Outbound\Persistence;

use App\Adapters\Outbound\Persistence\Models\UserModel;
use App\Application\Ports\Outbound\UserRepository;
use App\Domain\Role;
use App\Domain\User;

final class EloquentUserRepository implements UserRepository
{
    public function findById(string $id): ?User
    {
        $row = UserModel::query()->find($id);

        return $row === null ? null : $this->toDomain($row);
    }

    public function findByUsername(string $username): ?User
    {
        $row = UserModel::query()->where('username', $username)->first();

        return $row === null ? null : $this->toDomain($row);
    }

    public function existsByUsername(string $username): bool
    {
        return UserModel::query()->where('username', $username)->exists();
    }

    public function add(User $user): void
    {
        UserModel::query()->insert([
            'id' => $user->id(),
            'username' => $user->username(),
            'password_hash' => $user->passwordHash(),
            'role' => $user->role()->value,
        ]);
    }

    private function toDomain(UserModel $row): User
    {
        return User::reconstitute(
            (string) $row->id,
            (string) $row->username,
            (string) $row->password_hash,
            Role::from((string) $row->role),
        );
    }
}
