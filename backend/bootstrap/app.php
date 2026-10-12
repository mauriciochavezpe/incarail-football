<?php

use App\Exceptions\ApiErrorRenderer;
use App\Exceptions\ApiException;
use App\Http\Middleware\EnsureUserHasPermission;
use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\ForceJsonResponse;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\SubstituteBindings;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        apiPrefix: '', // el enunciado pide GET /competitions, GET /team... tal cual
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->prepend(ForceJsonResponse::class);
        $middleware->alias([
            'active' => EnsureUserIsActive::class,
            'permission' => EnsureUserHasPermission::class,
        ]);
        // Autorizar ANTES de resolver modelos de la ruta: sin permiso → 403,
        // no un 404 que revele si el registro existe.
        $middleware->prependToPriorityList(SubstituteBindings::class, EnsureUserIsActive::class);
        $middleware->prependToPriorityList(SubstituteBindings::class, EnsureUserHasPermission::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Errores de negocio esperados: ya se registran como warning donde ocurren.
        $exceptions->dontReport(ApiException::class);
        $exceptions->shouldRenderJsonWhen(fn (Request $request) => true);
        $exceptions->render(fn (Throwable $e, Request $request) => ApiErrorRenderer::render($e));
    })->create();
