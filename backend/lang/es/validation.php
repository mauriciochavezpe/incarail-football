<?php

// Mensajes de validación en español (solo las reglas que usa esta API).
return [
    'alpha_dash' => 'El campo :attribute solo puede contener letras, números, guiones y guiones bajos.',
    'array' => 'El campo :attribute debe ser una lista.',
    'between' => ['numeric' => 'El campo :attribute debe estar entre :min y :max.'],
    'boolean' => 'El campo :attribute debe ser verdadero o falso.',
    'distinct' => 'El campo :attribute tiene un valor duplicado.',
    'email' => 'El campo :attribute debe ser un correo válido.',
    'exists' => 'El :attribute seleccionado no existe.',
    'integer' => 'El campo :attribute debe ser un número entero.',
    'max' => ['string' => 'El campo :attribute no debe superar :max caracteres.', 'numeric' => 'El campo :attribute no debe ser mayor que :max.'],
    'min' => ['string' => 'El campo :attribute debe tener al menos :min caracteres.', 'array' => 'Debes elegir al menos :min :attribute.', 'numeric' => 'El campo :attribute debe ser al menos :min.'],
    'password' => [
        'letters' => 'La contraseña debe tener al menos una letra.',
        'numbers' => 'La contraseña debe tener al menos un número.',
    ],
    'present' => 'El campo :attribute debe estar presente.',
    'regex' => 'El formato de :attribute no es válido.',
    'required' => 'El campo :attribute es obligatorio.',
    'string' => 'El campo :attribute debe ser texto.',
    'unique' => 'Ese :attribute ya está registrado.',

    'attributes' => [
        'name' => 'nombre',
        'email' => 'correo',
        'password' => 'contraseña',
        'roles' => 'rol',
        'roles.*' => 'rol',
        'permissions' => 'permisos',
        'permissions.*' => 'permiso',
        'label' => 'nombre del rol',
        'description' => 'descripción',
        'is_active' => 'activo',
        'per_page' => 'resultados por página',
        'search' => 'búsqueda',
        'competition_id' => 'liga',
        'team_id' => 'equipo',
        'position' => 'posición',
    ],
];
