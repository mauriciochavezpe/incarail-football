<?php

return [

    /*
    | Cliente de football-data.org (API v4). El token NUNCA va en el código:
    | se lee de FOOTBALL_DATA_TOKEN en el .env.
    */

    'base_url' => env('FOOTBALL_DATA_BASE_URL', 'https://api.football-data.org/v4'),

    'token' => env('FOOTBALL_DATA_TOKEN'),

    // Segundos para conectar y para esperar la respuesta completa.
    'connect_timeout' => (int) env('FOOTBALL_DATA_CONNECT_TIMEOUT', 5),
    'timeout' => (int) env('FOOTBALL_DATA_TIMEOUT', 15),

    /*
    | Límite propio de peticiones salientes. El plan gratuito permite 10 por
    | minuto por token; el limitador es global (no por usuario) porque el cupo
    | pertenece al token, no a quien hace clic.
    */
    'rate_limit' => [
        'max_requests' => (int) env('FOOTBALL_DATA_MAX_REQUESTS_PER_MINUTE', 10),
        'window_seconds' => 60,
    ],

    /*
    | Cuánto tiempo se considera "fresca" la copia local antes de volver a
    | consultar football-data. Mientras esté fresca, la API responde solo con
    | la base de datos y no gasta cupo.
    */
    'ttl' => [
        'competitions_minutes' => (int) env('FOOTBALL_DATA_COMPETITIONS_TTL', 1440),
        'competition_teams_minutes' => (int) env('FOOTBALL_DATA_COMPETITION_TEAMS_TTL', 360),
    ],

];
