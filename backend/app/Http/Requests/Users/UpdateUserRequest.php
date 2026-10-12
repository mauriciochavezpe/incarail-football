<?php

namespace App\Http\Requests\Users;

use App\Http\Requests\ApiFormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateUserRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => ['sometimes', 'required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->route('user'))],
            'password' => ['sometimes', 'nullable', 'string', Password::min(8)->letters()->numbers()],
            'roles' => ['sometimes', 'array', 'min:1'],
            'roles.*' => ['integer', 'distinct', 'exists:roles,id'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
