<?php

namespace App\Http\Requests\Football;

use App\Http\Requests\ApiFormRequest;

class ListTeamsRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'search' => ['sometimes', 'nullable', 'string', 'max:100'],
            'competition_id' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'between:1,100'],
        ];
    }

    /** @return array{search?: string, competition_id?: int} */
    public function filters(): array
    {
        return array_filter([
            'search' => $this->validated('search'),
            'competition_id' => $this->filled('competition_id') ? (int) $this->validated('competition_id') : null,
        ]);
    }
}
