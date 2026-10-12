<?php

namespace App\Http\Requests\Football;

use App\Http\Requests\ApiFormRequest;

class ListPlayersRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'search' => ['sometimes', 'nullable', 'string', 'max:100'],
            'team_id' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'position' => ['sometimes', 'nullable', 'string', 'max:50'],
            'per_page' => ['sometimes', 'integer', 'between:1,100'],
        ];
    }

    /** @return array{search?: string, team_id?: int, position?: string} */
    public function filters(): array
    {
        return array_filter([
            'search' => $this->validated('search'),
            'team_id' => $this->filled('team_id') ? (int) $this->validated('team_id') : null,
            'position' => $this->validated('position'),
        ]);
    }
}
