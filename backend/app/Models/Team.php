<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Team extends Model
{
    public $incrementing = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['synced_at' => 'datetime', 'founded' => 'integer'];
    }

    public function competitions(): BelongsToMany
    {
        return $this->belongsToMany(Competition::class)->withPivot('season');
    }

    public function players(): HasMany
    {
        return $this->hasMany(Player::class);
    }

    /**
     * @param  array{search?: string, competition_id?: int}  $filters
     */
    public function scopeFilter(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['search'] ?? null, fn (Builder $q, string $search) => $q->where(
                fn (Builder $q) => $q->whereLike('name', "%{$search}%")
                    ->orWhereLike('short_name', "%{$search}%")
                    ->orWhereLike('tla', "%{$search}%")
            ))
            ->when($filters['competition_id'] ?? null, fn (Builder $q, int $id) => $q->whereHas(
                'competitions', fn (Builder $q) => $q->whereKey($id)
            ));
    }
}
