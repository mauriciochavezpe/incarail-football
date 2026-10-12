<?php

namespace App\Exceptions\FootballData;

/** No queda cupo de peticiones a football-data en la ventana actual. */
class RateLimitReachedException extends FootballDataException
{
    public function __construct(int $retryAfter)
    {
        parent::__construct(
            "Se alcanzó el límite de peticiones a football-data.org. Intenta de nuevo en {$retryAfter} segundos.",
            429,
            'football_data_rate_limited',
            max(1, $retryAfter),
        );
    }
}
