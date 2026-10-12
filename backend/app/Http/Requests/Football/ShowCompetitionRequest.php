<?php

namespace App\Http\Requests\Football;

use App\Http\Requests\ApiFormRequest;

/** Valida el parámetro de ruta: ID numérico (2021) o código (PL). */
class ShowCompetitionRequest extends ApiFormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge(['competition_id' => (string) $this->route('competition_id')]);
    }

    public function rules(): array
    {
        return [
            'competition_id' => ['required', 'string', 'regex:/^(\d{1,10}|[A-Za-z0-9]{2,10})$/'],
        ];
    }

    public function messages(): array
    {
        return ['competition_id.regex' => 'El identificador de la liga debe ser un ID numérico (2021) o un código (PL).'];
    }
}
