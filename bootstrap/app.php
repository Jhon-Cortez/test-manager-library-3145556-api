<?php

use App\Adapters\Inbound\Http\Middleware\AuthenticateToken;
use App\Adapters\Inbound\Http\Middleware\RequireRole;
use App\Application\Exceptions\BookHasOpenLoans;
use App\Application\Exceptions\DocumentTaken;
use App\Application\Exceptions\ForbiddenRole;
use App\Application\Exceptions\InvalidCredentials;
use App\Application\Exceptions\InvalidDateRange;
use App\Application\Exceptions\IsbnTaken;
use App\Application\Exceptions\MemberHasOverdue;
use App\Application\Exceptions\MemberLimitExceeded;
use App\Application\Exceptions\NotFound;
use App\Application\Exceptions\OptimisticLockConflict;
use App\Application\Exceptions\Unauthorized;
use App\Application\Exceptions\UsernameTaken;
use App\Domain\Exceptions\BookNotAvailable;
use App\Domain\Exceptions\DuplicateBookInLoan;
use App\Domain\Exceptions\InvalidLoanSize;
use App\Domain\Exceptions\InvalidValue;
use App\Domain\Exceptions\ItemAlreadyReturned;
use App\Domain\Exceptions\ItemNotFound;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        apiPrefix: 'api/v1',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'auth.token' => AuthenticateToken::class,
            'role' => RequireRole::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->render(function (\Throwable $e, Request $request) {
            [$status, $code] = match (true) {
                $e instanceof InvalidCredentials => [401, 'INVALID_CREDENTIALS'],
                $e instanceof Unauthorized => [401, 'UNAUTHORIZED'],
                $e instanceof ForbiddenRole => [403, 'FORBIDDEN_ROLE'],
                $e instanceof NotFound, $e instanceof ItemNotFound => [404, 'NOT_FOUND'],
                $e instanceof UsernameTaken => [409, 'USERNAME_TAKEN'],
                $e instanceof IsbnTaken => [409, 'ISBN_TAKEN'],
                $e instanceof DocumentTaken => [409, 'DOCUMENT_TAKEN'],
                $e instanceof BookHasOpenLoans => [409, 'BOOK_HAS_OPEN_LOANS'],
                $e instanceof BookNotAvailable, $e instanceof OptimisticLockConflict => [409, 'BOOK_NOT_AVAILABLE'],
                $e instanceof MemberHasOverdue => [409, 'MEMBER_HAS_OVERDUE'],
                $e instanceof ItemAlreadyReturned => [409, 'ITEM_ALREADY_RETURNED'],
                $e instanceof MemberLimitExceeded => [422, 'MEMBER_LIMIT_EXCEEDED'],
                $e instanceof DuplicateBookInLoan => [422, 'DUPLICATE_BOOK_IN_LOAN'],
                $e instanceof InvalidDateRange => [422, 'INVALID_DATE_RANGE'],
                $e instanceof InvalidValue, $e instanceof InvalidLoanSize => [422, 'VALIDATION_ERROR'],
                $e instanceof ValidationException => [422, 'VALIDATION_ERROR'],
                default => [null, null],
            };

            if ($status === null) {
                return null;
            }

            return response()->json([
                'code' => $code,
                'message' => $e instanceof ValidationException
                    ? 'Los datos enviados no son válidos.'
                    : $e->getMessage(),
                'details' => [],
            ], $status);
        });
    })->create();
