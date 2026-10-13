<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Adapters\Outbound\Persistence\EloquentUserRepository;
use App\Domain\Role;
use App\Domain\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

final class AuthContractTest extends TestCase
{
    use DatabaseTransactions;

    private function makeUser(string $username = 'ana', string $password = 'secreta', Role $role = Role::Librarian): User
    {
        $hasher = new \App\Adapters\Outbound\Security\BcryptPasswordHasher();
        $user = User::register('99999999-aaaa-4aaa-8aaa-999999999999', $username, $hasher->hash($password), $role);
        (new EloquentUserRepository())->add($user);

        return $user;
    }

    private function tokenFor(User $user): string
    {
        $issuer = new \App\Adapters\Outbound\Security\JwtTokenIssuer((string) env('JWT_SECRET'));

        return $issuer->issue($user, new \DateTimeImmutable('+1 hour'));
    }

    public function test_login_returns_an_eight_hour_token_and_the_role(): void
    {
        $this->makeUser();

        $response = $this->postJson('/api/v1/auth/login', ['username' => 'ana', 'password' => 'secreta']);

        $response->assertOk()->assertJsonStructure(['access_token', 'token_type', 'expires_in', 'role'])
            ->assertJson(['token_type' => 'Bearer', 'expires_in' => 28800, 'role' => 'librarian']);
    }

    public function test_login_with_bad_credentials_never_reveals_which_one_failed(): void
    {
        $this->makeUser();

        $response = $this->postJson('/api/v1/auth/login', ['username' => 'ana', 'password' => 'otra']);

        $response->assertStatus(401)->assertJson(['code' => 'INVALID_CREDENTIALS']);
        self::assertStringNotContainsString('ana', $response->json('message'));
        self::assertStringNotContainsString('clave', $response->json('message'));
    }

    public function test_protected_routes_reject_requests_without_token(): void
    {
        $response = $this->getJson('/api/v1/categories');

        $response->assertStatus(401)->assertJson(['code' => 'UNAUTHORIZED']);
    }

    public function test_protected_routes_reject_invalid_tokens(): void
    {
        $response = $this->getJson('/api/v1/categories', ['Authorization' => 'Bearer token-falso']);

        $response->assertStatus(401)->assertJson(['code' => 'UNAUTHORIZED']);
    }

    public function test_authenticated_users_list_the_five_categories_by_name(): void
    {
        $token = $this->tokenFor($this->makeUser());

        $response = $this->getJson('/api/v1/categories', ['Authorization' => 'Bearer ' . $token]);

        $response->assertOk();
        $items = $response->json('items');
        self::assertCount(5, $items);
        self::assertSame(['Ciencia', 'Historia', 'Infantil', 'Novela', 'Tecnología'], array_column($items, 'name'));
    }
}
