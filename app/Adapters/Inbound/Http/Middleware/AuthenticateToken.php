<?php

declare(strict_types=1);

namespace App\Adapters\Inbound\Http\Middleware;

use App\Application\Exceptions\Unauthorized;
use App\Application\Ports\Outbound\TokenParser;
use Closure;
use Illuminate\Http\Request;
use Throwable;

final class AuthenticateToken
{
    public function __construct(private readonly TokenParser $tokens)
    {
    }

    public function handle(Request $request, Closure $next)
    {
        $header = (string) $request->header('Authorization', '');
        if (! str_starts_with($header, 'Bearer ')) {
            throw new Unauthorized();
        }

        try {
            $claims = $this->tokens->parse(substr($header, 7));
        } catch (Throwable) {
            throw new Unauthorized();
        }

        $request->attributes->set('user_id', $claims['sub']);
        $request->attributes->set('user_role', $claims['role']);

        return $next($request);
    }
}
