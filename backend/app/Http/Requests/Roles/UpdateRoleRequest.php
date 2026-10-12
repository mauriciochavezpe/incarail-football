<?php

namespace App\Http\Requests\Roles;

use App\Http\Requests\ApiFormRequest;

class UpdateRoleRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'label' => ['sometimes', 'required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:255'],
            'permissions' => ['sometimes', 'array'],
            'permissions.*' => ['integer', 'distinct', 'exists:permissions,id'],
        ];
    }
}
