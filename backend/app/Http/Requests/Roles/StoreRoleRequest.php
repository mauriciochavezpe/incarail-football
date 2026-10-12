<?php

namespace App\Http\Requests\Roles;

use App\Http\Requests\ApiFormRequest;

class StoreRoleRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'label' => ['required', 'string', 'max:100'],
            'name' => ['sometimes', 'string', 'max:50', 'alpha_dash', 'unique:roles,name'],
            'description' => ['nullable', 'string', 'max:255'],
            'permissions' => ['present', 'array'],
            'permissions.*' => ['integer', 'distinct', 'exists:permissions,id'],
        ];
    }
}
