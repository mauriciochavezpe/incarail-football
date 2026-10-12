<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Usuarios de prueba (documentados en el README). Idempotente.
 */
class UserSeeder extends Seeder
{
    public const array USERS = [
        ['name' => 'Admin Inca Rail', 'email' => 'admin@incarail.test', 'role' => 'admin'],
        ['name' => 'Ana Analista', 'email' => 'analista@incarail.test', 'role' => 'analyst'],
        ['name' => 'Carlos Consulta', 'email' => 'consulta@incarail.test', 'role' => 'viewer'],
    ];

    public function run(): void
    {
        foreach (self::USERS as $data) {
            $user = User::updateOrCreate(
                ['email' => $data['email']],
                ['name' => $data['name'], 'password' => 'Pa$$word123!.', 'is_active' => true],
            );

            $user->roles()->sync([Role::where('name', $data['role'])->value('id')]);
        }
    }
}
