<?php

namespace App\Http\Resources;

use App\Models\Competition;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Competition */
class CompetitionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'type' => $this->type,
            'plan' => $this->plan,
            'emblem' => $this->emblem,
            'area' => ['name' => $this->area_name, 'code' => $this->area_code],
            'current_season' => [
                'start' => $this->current_season_start?->toDateString(),
                'end' => $this->current_season_end?->toDateString(),
                'matchday' => $this->current_matchday,
            ],
            'imported' => $this->isImported(),
            'teams_synced_at' => $this->teams_synced_at?->toIso8601String(),
            'teams_count' => $this->whenCounted('teams'),
            'teams' => TeamResource::collection($this->whenLoaded('teams')),
        ];
    }
}
