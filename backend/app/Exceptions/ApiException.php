<?php

namespace App\Exceptions;

use RuntimeException;
use Throwable;

/**
 * Error de negocio que ya sabe con qué código HTTP y qué "code" responder.
 * El render central (bootstrap/app.php) lo convierte al formato JSON común.
 */
abstract class ApiException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly int $status,
        public readonly string $errorCode,
        public readonly ?int $retryAfter = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
