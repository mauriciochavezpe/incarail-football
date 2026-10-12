<?php

namespace App\Services\FootballData;

use App\Exceptions\FootballData\FootballDataException;
use App\Exceptions\FootballData\FootballDataResourceException;
use App\Exceptions\FootballData\FootballDataUnavailableException;
use App\Exceptions\FootballData\RateLimitReachedException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory as Http;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Log;

/**
 * ÚNICA clase que habla con football-data.org (API v4).
 *
 * Responsabilidades: autenticar con el token del .env, pedir cupo al
 * limitador antes de cada llamada, sincronizar el limitador con las cabeceras
 * de respuesta y traducir cualquier fallo HTTP a una excepción propia.
 * No sabe nada de la base de datos.
 */
class FootballDataClient
{
    public function __construct(
        private readonly Http $http,
        private readonly FootballDataRateLimiter $limiter,
        private readonly string $baseUrl,
        private readonly ?string $token,
        private readonly int $connectTimeout = 5,
        private readonly int $timeout = 15,
    ) {}

    /**
     * Catálogo de competiciones disponibles para el token (1 petición).
     *
     * @return list<array<string, mixed>>
     */
    public function competitions(): array
    {
        $this->ensureConfigured();
        $this->limiter->acquire();

        return $this->get('/competitions', 'El catálogo de competiciones')['competitions'] ?? [];
    }

    /**
     * Detalle de una competición y sus equipos con plantel (2 peticiones).
     *
     * El endpoint /competitions/{id}/teams ya trae el "squad" de cada equipo,
     * así que importar una liga completa cuesta 2 peticiones y no 1 + N.
     * El cupo de ambas se reserva junto para no quedar a medias.
     *
     * @return array{competition: array<string, mixed>, teams: list<array<string, mixed>>, season: ?array<string, mixed>}
     *
     * @throws FootballDataException
     */
    public function competitionWithTeams(string $idOrCode): array
    {
        $this->ensureConfigured();
        $this->limiter->acquire(2);

        $resource = "La competición {$idOrCode}";
        $competition = $this->get('/competitions/'.rawurlencode($idOrCode), $resource);
        $teams = $this->get('/competitions/'.rawurlencode($idOrCode).'/teams', $resource);

        return [
            'competition' => $competition,
            'teams' => $teams['teams'] ?? [],
            'season' => $teams['season'] ?? null,
        ];
    }

    /**
     * @return array<string, mixed>
     *
     * @throws FootballDataException
     */
    private function get(string $path, string $resource): array
    {
        try {
            $response = $this->http
                ->baseUrl($this->baseUrl)
                ->withHeaders(['X-Auth-Token' => $this->token])
                ->acceptJson()
                ->connectTimeout($this->connectTimeout)
                ->timeout($this->timeout)
                ->get($path);
        } catch (ConnectionException $e) {
            Log::warning('football-data: sin conexión', ['path' => $path, 'error' => $e->getMessage()]);
            throw FootballDataUnavailableException::connection($e);
        }

        $this->limiter->syncWithUpstream(
            $this->intHeader($response, 'X-Requests-Available-Minute'),
            $this->intHeader($response, 'X-RequestCounter-Reset'),
        );

        if ($response->successful()) {
            return $response->json() ?? [];
        }

        Log::warning('football-data: respuesta con error', ['path' => $path, 'status' => $response->status()]);

        throw match (true) {
            $response->status() === 429 => $this->upstreamRateLimited($response),
            $response->status() === 404 => FootballDataResourceException::notFound($resource),
            $response->status() === 403 => FootballDataResourceException::restricted($resource),
            $response->status() === 400 => FootballDataResourceException::notFound($resource),
            $response->status() === 401 => FootballDataUnavailableException::unauthorized(),
            default => FootballDataUnavailableException::badGateway($response->status()),
        };
    }

    /** Falla antes de gastar cupo si no hay token configurado. */
    private function ensureConfigured(): void
    {
        if (blank($this->token)) {
            throw FootballDataUnavailableException::notConfigured();
        }
    }

    private function upstreamRateLimited(Response $response): RateLimitReachedException
    {
        $retryAfter = $this->intHeader($response, 'X-RequestCounter-Reset') ?? 60;
        $this->limiter->blockFor($retryAfter);

        return new RateLimitReachedException($retryAfter);
    }

    private function intHeader(Response $response, string $name): ?int
    {
        $value = $response->header($name);

        return is_numeric($value) ? (int) $value : null;
    }
}
