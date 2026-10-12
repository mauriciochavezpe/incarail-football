<?php

namespace App\Http\Resources;

use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Team */
class TeamResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'short_name' => $this->short_name,
            'tla' => $this->tla,
            'crest' => $this->crest,
            'area' => $this->area_name,
            'founded' => $this->founded,
            'club_colors' => $this->club_colors,
            'venue' => $this->venue,
            'website' => $this->website,
            'address' => $this->address,
            'coach' => $this->coach_name,
            'synced_at' => $this->synced_at?->toIso8601String(),
            'players_count' => $this->whenCounted('players'),
            'competitions' => $this->whenLoaded('competitions', fn () => $this->competitions->map(fn ($c) => [
                'id' => $c->id, 'code' => $c->code, 'name' => $c->name, 'emblem' => $c->emblem, 'season' => $c->pivot?->season,
            ])),
            'players' => PlayerResource::collection($this->whenLoaded('players')),
        ];
    }
}
