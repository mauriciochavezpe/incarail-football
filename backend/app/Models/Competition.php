<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Competition extends Model
{
    /** El ID es el de football-data. */
    public $incrementing = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'current_season_start' => 'date',
            'current_season_end' => 'date',
            'synced_at' => 'datetime',
            'teams_synced_at' => 'datetime',
        ];
    }

    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class)->withPivot('season');
    }

    /** Acepta el ID numérico (2021) o el código (PL). */
    public function scopeIdOrCode(Builder $query, string $value): Builder
    {
        return ctype_digit($value)
            ? $query->whereKey((int) $value)
            : $query->where('code', strtoupper($value));
    }

    public function isImported(): bool
    {
        return $this->teams_synced_at !== null;
    }
}
