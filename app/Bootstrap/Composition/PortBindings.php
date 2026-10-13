<?php

declare(strict_types=1);

namespace App\Bootstrap\Composition;

use App\Adapters\Outbound\Persistence\EloquentBookRepository;
use App\Adapters\Outbound\Persistence\EloquentCategoryReader;
use App\Adapters\Outbound\Persistence\EloquentLoanRepository;
use App\Adapters\Outbound\Persistence\EloquentMemberRepository;
use App\Adapters\Outbound\Persistence\EloquentReportReader;
use App\Adapters\Outbound\Persistence\EloquentUserRepository;
use App\Adapters\Outbound\Security\BcryptPasswordHasher;
use App\Adapters\Outbound\Security\JwtTokenIssuer;
use App\Adapters\Outbound\Storage\DiskCoverStorage;
use App\Adapters\Outbound\Time\SystemClock;
use App\Application\Ports\Outbound\BookRepository;
use App\Application\Ports\Outbound\CategoryReader;
use App\Application\Ports\Outbound\Clock;
use App\Application\Ports\Outbound\CoverStorage;
use App\Application\Ports\Outbound\LoanRepository;
use App\Application\Ports\Outbound\MemberRepository;
use App\Application\Ports\Outbound\PasswordHasher;
use App\Application\Ports\Outbound\ReportReader;
use App\Application\Ports\Outbound\TokenIssuer;
use App\Application\Ports\Outbound\TokenParser;
use App\Application\Ports\Outbound\UserRepository;
use Illuminate\Contracts\Foundation\Application;

final class PortBindings
{
    public static function register(Application $app): void
    {
        $app->singleton(Clock::class, static fn (): Clock => new SystemClock());
        $app->singleton(PasswordHasher::class, static fn (): PasswordHasher => new BcryptPasswordHasher());
        $app->singleton(
            JwtTokenIssuer::class,
            static fn (): JwtTokenIssuer => new JwtTokenIssuer((string) env('JWT_SECRET')),
        );
        $app->singleton(
            TokenIssuer::class,
            static fn (Application $app): TokenIssuer => $app->make(JwtTokenIssuer::class),
        );
        $app->singleton(
            CoverStorage::class,
            static fn (): CoverStorage => new DiskCoverStorage((string) (env('COVERS_DIR') ?: base_path('var/covers'))),
        );
        $app->singleton(
            TokenParser::class,
            static fn (Application $app): TokenParser => $app->make(JwtTokenIssuer::class),
        );
        $app->singleton(BookRepository::class, static fn (): BookRepository => new EloquentBookRepository());
        $app->singleton(MemberRepository::class, static fn (): MemberRepository => new EloquentMemberRepository());
        $app->singleton(UserRepository::class, static fn (): UserRepository => new EloquentUserRepository());
        $app->singleton(LoanRepository::class, static fn (): LoanRepository => new EloquentLoanRepository());
        $app->singleton(CategoryReader::class, static fn (): CategoryReader => new EloquentCategoryReader());
        $app->singleton(ReportReader::class, static fn (): ReportReader => new EloquentReportReader());
    }
}
