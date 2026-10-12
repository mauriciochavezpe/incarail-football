<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

/**
 * Permisos (formato CASL: action + subject) y roles base. Idempotente.
 */
class AccessControlSeeder extends Seeder
{
    /** @var array<string, string> "action:Subject" => etiqueta */
    public const array PERMISSIONS = [
        'read:Competition' => 'Ver competiciones',
        'import:Competition' => 'Importar ligas desde football-data',
        'read:Team' => 'Ver equipos',
        'read:Player' => 'Ver jugadores',
        'read:User' => 'Ver usuarios',
        'create:User' => 'Crear usuarios',
        'update:User' => 'Editar, activar y desactivar usuarios',
        'read:Role' => 'Ver roles y permisos',
        'create:Role' => 'Crear roles',
        'update:Role' => 'Editar roles',
        'delete:Role' => 'Eliminar roles',
    ];

    public const array ROLES = [
        'admin' => [
            'label' => 'Administrador',
            'description' => 'Acceso total: datos de fútbol, usuarios, roles y permisos.',
            'permissions' => '*',
        ],
        'analyst' => [
            'label' => 'Analista',
            'description' => 'Consulta los datos e importa ligas nuevas desde football-data.',
            'permissions' => ['read:Competition', 'import:Competition', 'read:Team', 'read:Player'],
        ],
        'viewer' => [
            'label' => 'Solo consulta',
            'description' => 'Solo lee lo que ya está guardado. No importa ni administra.',
            'permissions' => ['read:Competition', 'read:Team', 'read:Player'],
        ],
    ];

    public function run(): void
    {
        $ids = [];
        foreach (self::PERMISSIONS as $key => $label) {
            [$action, $subject] = explode(':', $key);
            $ids[$key] = Permission::updateOrCreate(compact('action', 'subject'), ['label' => $label])->id;
        }

        foreach (self::ROLES as $name => $role) {
            $model = Role::updateOrCreate(
                ['name' => $name],
                ['label' => $role['label'], 'description' => $role['description'], 'is_system' => true],
            );

            $model->permissions()->sync(
                $role['permissions'] === '*' ? array_values($ids) : array_map(fn ($k) => $ids[$k], $role['permissions'])
            );
        }
    }
}
