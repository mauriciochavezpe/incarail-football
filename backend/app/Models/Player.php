<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Player extends Model
{
    public $incrementing = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['date_of_birth' => 'date', 'shirt_number' => 'integer'];
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * @param  array{search?: string, team_id?: int, position?: string}  $filters
     */
    public function scopeFilter(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['search'] ?? null, fn (Builder $q, string $s) => $q->whereLike('name', "%{$s}%"))
            ->when($filters['team_id'] ?? null, fn (Builder $q, int $id) => $q->where('team_id', $id))
            ->when($filters['position'] ?? null, fn (Builder $q, string $p) => $q->where('position', $p));
    }
}
