<?php

namespace App\Services\Football;

use Illuminate\Support\Carbon;

/**
 * Metadatos de dónde salió una respuesta: de la base local o recién traída de
 * football-data, y si la copia local está desactualizada porque no se pudo
 * refrescar. El frontend lo usa para avisar sin romper la pantalla.
 */
final readonly class DataSource
{
    private function __construct(
        public string $source,
        public bool $stale,
        public ?Carbon $syncedAt,
        public ?string $warning = null,
    ) {}

    public static function remote(): self
    {
        return new self('football-data', false, now());
    }

    public static function local(?Carbon $syncedAt): self
    {
        return new self('database', false, $syncedAt);
    }

    public static function staleLocal(?Carbon $syncedAt, string $warning): self
    {
        return new self('database', true, $syncedAt, $warning);
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return array_filter([
            'source' => $this->source,
            'stale' => $this->stale,
            'synced_at' => $this->syncedAt?->toIso8601String(),
            'warning' => $this->warning,
        ], fn ($v) => $v !== null);
    }
}
