<?php

namespace App\Exceptions\FootballData;

/** El recurso no existe o el plan del token no da acceso a él. */
class FootballDataResourceException extends FootballDataException
{
    public static function notFound(string $resource): self
    {
        return new self("{$resource} no existe en football-data.org.", 404, 'football_data_not_found');
    }

    public static function restricted(string $resource): self
    {
        return new self("{$resource} no está disponible en el plan gratuito de football-data.org.", 403, 'football_data_restricted');
    }
}
