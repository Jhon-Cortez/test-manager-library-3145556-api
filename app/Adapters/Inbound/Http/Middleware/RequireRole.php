<?php

declare(strict_types=1);

namespace App\Adapters\Inbound\Http\Middleware;

use App\Application\Exceptions\ForbiddenRole;
use Closure;
use Illuminate\Http\Request;

final class RequireRole
{
    public function handle(Request $request, Closure $next, string $role)
    {
        if ($request->attributes->get('user_role') !== $role) {
            throw new ForbiddenRole();
        }

        return $next($request);
    }
}
