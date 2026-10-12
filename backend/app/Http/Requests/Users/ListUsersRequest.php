<?php

namespace App\Http\Requests\Users;

use App\Http\Requests\ApiFormRequest;

class ListUsersRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'search' => ['sometimes', 'nullable', 'string', 'max:100'],
            'per_page' => ['sometimes', 'integer', 'between:1,100'],
        ];
    }
}
