<?php

namespace App\Exceptions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

/**
 * Formato ÚNICO de error para toda la API:
 *
 * { "error": { "status": 404, "code": "not_found", "message": "...", "errors": {...}? } }
 */
final class ApiErrorRenderer
{
    private const array MODEL_NAMES = [
        'App\Models\Team' => 'El equipo',
        'App\Models\Player' => 'El jugador',
        'App\Models\Competition' => 'La competición',
        'App\Models\User' => 'El usuario',
        'App\Models\Role' => 'El rol',
    ];

    public static function render(Throwable $e): JsonResponse
    {
        return match (true) {
            $e instanceof ApiException => self::json($e->status, $e->errorCode, $e->getMessage(), retryAfter: $e->retryAfter),

            $e instanceof ValidationException => self::json(422, 'validation_failed', 'Los datos enviados no son válidos.', $e->errors()),

            $e instanceof AuthenticationException => self::json(401, 'unauthenticated',
                $e->getMessage() === 'Unauthenticated.' ? 'Tu sesión no es válida o venció. Inicia sesión de nuevo.' : $e->getMessage()),

            $e instanceof AuthorizationException, $e instanceof AccessDeniedHttpException => self::json(403, 'forbidden',
                $e->getMessage() ?: 'No tienes permiso para esta acción.'),

            $e instanceof ModelNotFoundException => self::json(404, 'not_found',
                (self::MODEL_NAMES[$e->getModel()] ?? 'El recurso').' no existe.'),

            $e instanceof NotFoundHttpException && $e->getPrevious() instanceof ModelNotFoundException => self::render($e->getPrevious()),

            $e instanceof NotFoundHttpException => self::json(404, 'not_found', 'La ruta solicitada no existe.'),

            $e instanceof MethodNotAllowedHttpException => self::json(405, 'method_not_allowed', 'Método HTTP no permitido para esta ruta.'),

            $e instanceof ThrottleRequestsException => self::json(429, 'too_many_requests',
                'Hiciste demasiadas peticiones. Espera un momento e intenta de nuevo.',
                retryAfter: (int) ($e->getHeaders()['Retry-After'] ?? 60)),

            $e instanceof LockTimeoutException => self::json(503, 'import_in_progress',
                'Esta liga se está importando en este momento. Intenta de nuevo en unos segundos.', retryAfter: 10),

            $e instanceof HttpExceptionInterface => self::json($e->getStatusCode(), 'http_error', $e->getMessage() ?: 'Error HTTP.'),

            default => self::unexpected($e),
        };
    }

    private static function unexpected(Throwable $e): JsonResponse
    {
        report($e);

        return self::json(500, 'server_error', config('app.debug') ? $e->getMessage() : 'Ocurrió un error inesperado.');
    }

    /** @param array<string, list<string>>|null $errors */
    private static function json(int $status, string $code, string $message, ?array $errors = null, ?int $retryAfter = null): JsonResponse
    {
        $body = ['status' => $status, 'code' => $code, 'message' => $message];
        if ($errors !== null) {
            $body['errors'] = $errors;
        }
        if ($retryAfter !== null) {
            $body['retry_after'] = $retryAfter;
        }

        $response = new JsonResponse(['error' => $body], $status);
        if ($retryAfter !== null) {
            $response->headers->set('Retry-After', (string) $retryAfter);
        }

        return $response;
    }
}
