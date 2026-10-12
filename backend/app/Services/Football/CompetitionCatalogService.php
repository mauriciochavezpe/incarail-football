<?php

namespace App\Services\Football;

use App\Exceptions\FootballData\FootballDataException;
use App\Models\Competition;
use App\Services\FootballData\FootballDataClient;
use App\Services\FootballData\FootballDataMapper;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * GET /competitions: catálogo de ligas.
 *
 * Se responde desde la base de datos. Solo si el catálogo está vacío o
 * vencido (TTL) se gasta 1 petición en refrescarlo. Si football-data falla y
 * ya hay datos guardados, se devuelven marcados como "stale" en vez de error.
 */
class CompetitionCatalogService
{
    public function __construct(
        private readonly FootballDataClient $client,
        private readonly FootballDataMapper $mapper,
        private readonly int $ttlMinutes,
    ) {}

    /**
     * @return array{0: Collection<int, Competition>, 1: DataSource}
     *
     * @throws FootballDataException si no hay copia local y football-data no responde
     */
    public function list(): array
    {
        $lastSync = Competition::query()->max('synced_at');
        $lastSync = $lastSync ? Carbon::parse($lastSync) : null;

        if ($lastSync && $lastSync->gt(now()->subMinutes($this->ttlMinutes))) {
            return [$this->stored(), DataSource::local($lastSync)];
        }

        try {
            $this->refresh();

            return [$this->stored(), DataSource::remote()];
        } catch (FootballDataException $e) {
            if ($lastSync === null) {
                throw $e;
            }

            return [$this->stored(), DataSource::staleLocal($lastSync, $e->getMessage())];
        }
    }

    private function refresh(): void
    {
        $rows = array_map(
            fn (array $c) => $this->mapper->competition($c) + ['synced_at' => now()],
            $this->client->competitions(),
        );

        DB::transaction(fn () => Competition::upsert(
            $rows,
            uniqueBy: ['id'],
            update: array_keys(collect($rows)->first() ?? ['name' => null]),
        ));
    }

    /** @return Collection<int, Competition> */
    private function stored(): Collection
    {
        return Competition::query()->withCount('teams')->orderBy('area_name')->orderBy('name')->get();
    }
}
