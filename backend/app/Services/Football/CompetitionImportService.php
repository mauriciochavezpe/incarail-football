<?php

namespace App\Services\Football;

use App\Exceptions\FootballData\FootballDataException;
use App\Exceptions\ImportNotAllowedException;
use App\Models\Competition;
use App\Models\Player;
use App\Models\Team;
use App\Services\FootballData\FootballDataClient;
use App\Services\FootballData\FootballDataMapper;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * GET /competitions/{id}: detalle de una liga + importación de sus equipos y
 * jugadores a la base local.
 *
 * Reglas:
 *  1. Si la liga ya se importó y no venció el TTL → se responde desde la BD
 *     (0 peticiones externas).
 *  2. Si hay que importar y el usuario tiene "import Competition" → 2
 *     peticiones (detalle + equipos con plantel) y upsert en una transacción.
 *  3. Si football-data falla o no hay cupo y existe copia local → se responde
 *     la copia marcada como "stale". Sin copia local → se propaga el error
 *     (429 / 502 / 503 / 404).
 *  4. Un lock por liga evita que dos clics simultáneos importen dos veces.
 */
class CompetitionImportService
{
    public function __construct(
        private readonly FootballDataClient $client,
        private readonly FootballDataMapper $mapper,
        private readonly int $ttlMinutes,
    ) {}

    /**
     * @return array{0: Competition, 1: DataSource}
     */
    public function show(string $idOrCode, bool $canImport): array
    {
        $local = $this->findLocal($idOrCode);

        if ($local?->isImported() && $local->teams_synced_at->gt(now()->subMinutes($this->ttlMinutes))) {
            return [$this->withTeams($local), DataSource::local($local->teams_synced_at)];
        }

        if (! $canImport) {
            if ($local?->isImported()) {
                return [$this->withTeams($local), DataSource::local($local->teams_synced_at)];
            }
            throw new ImportNotAllowedException;
        }

        $key = 'competition-import:'.strtoupper($idOrCode);

        return Cache::lock($key, 30)->block(20, function () use ($idOrCode, $local) {
            // Otro proceso pudo terminar la importación mientras esperábamos el lock.
            $fresh = $this->findLocal($idOrCode);
            if ($fresh?->isImported() && $fresh->teams_synced_at->gt(now()->subMinutes($this->ttlMinutes))) {
                return [$this->withTeams($fresh), DataSource::local($fresh->teams_synced_at)];
            }

            try {
                $payload = $this->client->competitionWithTeams($idOrCode);
            } catch (FootballDataException $e) {
                if ($local?->isImported()) {
                    return [$this->withTeams($local), DataSource::staleLocal($local->teams_synced_at, $e->getMessage())];
                }
                throw $e;
            }

            return [$this->withTeams($this->persist($payload)), DataSource::remote()];
        });
    }

    /**
     * Upsert idempotente: las PK son los IDs de football-data, así que
     * importar la misma liga N veces no duplica nada.
     *
     * @param  array{competition: array<string, mixed>, teams: list<array<string, mixed>>, season: ?array<string, mixed>}  $payload
     */
    private function persist(array $payload): Competition
    {
        return DB::transaction(function () use ($payload) {
            $now = now();

            $competition = Competition::updateOrCreate(
                ['id' => (int) $payload['competition']['id']],
                // synced_at NO se toca aquí: marca la vigencia del catálogo (GET /competitions),
                // y una importación individual no significa que el catálogo esté al día.
                $this->mapper->competition($payload['competition']) + ['teams_synced_at' => $now],
            );

            $season = $this->mapper->seasonLabel($payload['season'] ?? $payload['competition']['currentSeason'] ?? null);
            $teamRows = [];
            $playerRows = [];
            $squadIdsByTeam = [];

            foreach ($payload['teams'] as $team) {
                $teamRows[(int) $team['id']] = $this->mapper->team($team) + ['synced_at' => $now];
                $squadIdsByTeam[(int) $team['id']] = [];

                foreach ($team['squad'] ?? [] as $player) {
                    // Indexado por ID: si un jugador aparece dos veces, no se duplica en el upsert.
                    $playerRows[(int) $player['id']] = $this->mapper->player($player, (int) $team['id']);
                    $squadIdsByTeam[(int) $team['id']][] = (int) $player['id'];
                }
            }

            foreach (array_chunk(array_values($teamRows), 100) as $chunk) {
                Team::upsert($chunk, uniqueBy: ['id'], update: array_keys($chunk[0]));
            }

            foreach (array_chunk(array_values($playerRows), 200) as $chunk) {
                Player::upsert($chunk, uniqueBy: ['id'], update: array_keys($chunk[0]));
            }

            // Jugadores que ya no están en el plantel: se conservan, pero sin equipo.
            foreach ($squadIdsByTeam as $teamId => $playerIds) {
                Player::query()->where('team_id', $teamId)->whereNotIn('id', $playerIds)->update(['team_id' => null]);
            }

            $competition->teams()->sync(
                collect(array_keys($squadIdsByTeam))->mapWithKeys(fn (int $id) => [$id => ['season' => $season]])->all()
            );

            return $competition;
        });
    }

    private function findLocal(string $idOrCode): ?Competition
    {
        return Competition::query()->idOrCode($idOrCode)->first();
    }

    private function withTeams(Competition $competition): Competition
    {
        return $competition->load(['teams' => fn ($q) => $q->withCount('players')->orderBy('name')])->loadCount('teams');
    }
}
