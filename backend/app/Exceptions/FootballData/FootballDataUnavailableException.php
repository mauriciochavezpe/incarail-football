<?php

namespace App\Exceptions\FootballData;

use Throwable;

/** Sin conexión, timeout, error 5xx o servicio sin configurar. */
class FootballDataUnavailableException extends FootballDataException
{
    public static function connection(Throwable $previous): self
    {
        return new self('No se pudo conectar con football-data.org. Intenta más tarde.', 503, 'football_data_unavailable', 30, $previous);
    }

    public static function badGateway(int $upstreamStatus): self
    {
        return new self("football-data.org respondió con un error ({$upstreamStatus}).", 502, 'football_data_bad_gateway', 30);
    }

    public static function notConfigured(): self
    {
        return new self('El token de football-data.org no está configurado (FOOTBALL_DATA_TOKEN).', 503, 'football_data_not_configured');
    }

    public static function unauthorized(): self
    {
        return new self('football-data.org rechazó el token configurado.', 502, 'football_data_invalid_token');
    }
}
