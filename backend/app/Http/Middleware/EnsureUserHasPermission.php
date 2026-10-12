<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Autorización en el servidor: ->middleware('permission:read,Team').
 * El frontend oculta botones con las mismas reglas, pero quien decide es esto.
 */
class EnsureUserHasPermission
{
    public function handle(Request $request, Closure $next, string $action, string $subject): Response
    {
        if (! $request->user()?->hasPermission($action, $subject)) {
            throw new AuthorizationException("No tienes permiso para esta acción ({$action} {$subject}).");
        }

        return $next($request);
    }
}
