<?php

declare(strict_types=1);

namespace Tests\Integration\Security;

use App\Adapters\Outbound\Security\BcryptPasswordHasher;
use App\Adapters\Outbound\Security\JwtTokenIssuer;
use App\Domain\Role;
use App\Domain\User;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class SecurityAdaptersTest extends TestCase
{
    public function test_bcrypt_hash_uses_factor_twelve_and_never_keeps_the_plain_password(): void
    {
        $hasher = new BcryptPasswordHasher();

        $hash = $hasher->hash('secreta');

        self::assertNotSame('secreta', $hash);
        self::assertSame(12, password_get_info($hash)['options']['cost']);
        self::assertTrue($hasher->verify('secreta', $hash));
        self::assertFalse($hasher->verify('otra', $hash));
    }

    public function test_jwt_carries_the_user_uuid_and_role_without_personal_data(): void
    {
        $issuer = new JwtTokenIssuer('secret-de-prueba-largo-y-aleatorio');
        $user = User::register('99999999-aaaa-4aaa-8aaa-999999999999', 'ana', 'hashed:secret', Role::Librarian);
        $expiresAt = new DateTimeImmutable('+8 hours');

        $claims = $issuer->parse($issuer->issue($user, $expiresAt));

        self::assertSame('99999999-aaaa-4aaa-8aaa-999999999999', $claims['sub']);
        self::assertSame('librarian', $claims['role']);
        self::assertSame(['sub', 'role'], array_keys($claims));
        self::assertArrayNotHasKey('username', $claims);
        self::assertArrayNotHasKey('password', $claims);
    }
}
