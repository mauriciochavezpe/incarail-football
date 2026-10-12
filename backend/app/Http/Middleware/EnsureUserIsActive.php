<?php

namespace App\Http\Middleware;

use App\Exceptions\AccountDisabledException;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Un usuario desactivado no puede seguir usando un token emitido antes. */
class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->is_active) {
            $user->revokeAllTokens();
            throw new AccountDisabledException;
        }

        return $next($request);
    }
}
