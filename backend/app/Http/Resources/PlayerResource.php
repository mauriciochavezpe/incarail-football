<?php

namespace App\Http\Resources;

use App\Models\Player;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Player */
class PlayerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'position' => $this->position,
            'shirt_number' => $this->shirt_number,
            'date_of_birth' => $this->date_of_birth?->toDateString(),
            'nationality' => $this->nationality,
            'team' => $this->whenLoaded('team', fn () => $this->team ? [
                'id' => $this->team->id,
                'name' => $this->team->name,
                'crest' => $this->team->crest,
                'competitions' => $this->team->relationLoaded('competitions')
                    ? $this->team->competitions->map(fn ($c) => ['id' => $c->id, 'code' => $c->code, 'name' => $c->name])
                    : null,
            ] : null),
        ];
    }
}
