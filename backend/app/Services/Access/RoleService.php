<?php

namespace App\Services\Access;

use App\Exceptions\DomainRuleException;
use App\Models\Role;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RoleService
{
    /** @param array{label: string, name?: string, description?: ?string, permissions: list<int>} $data */
    public function create(array $data): Role
    {
        return DB::transaction(function () use ($data) {
            $role = Role::create([
                'name' => $data['name'] ?? Str::slug($data['label']),
                'label' => $data['label'],
                'description' => $data['description'] ?? null,
                'is_system' => false,
            ]);
            $role->permissions()->sync($data['permissions']);

            return $role->load('permissions')->loadCount('users');
        });
    }

    /** @param array<string, mixed> $data */
    public function update(Role $role, array $data): Role
    {
        if ($role->is_system && $role->name === 'admin' && array_key_exists('permissions', $data)) {
            throw new DomainRuleException('Los permisos del rol administrador no se pueden modificar.', 'system_role_locked');
        }

        return DB::transaction(function () use ($role, $data) {
            $role->fill(collect($data)->only(['label', 'description'])->all())->save();

            if (array_key_exists('permissions', $data)) {
                $role->permissions()->sync($data['permissions']);
            }

            return $role->load('permissions')->loadCount('users');
        });
    }

    public function delete(Role $role): void
    {
        if ($role->is_system) {
            throw new DomainRuleException('Los roles base del sistema no se pueden eliminar.', 'system_role_locked');
        }
        if ($role->users()->exists()) {
            throw new DomainRuleException('El rol tiene usuarios asignados. Reasígnalos antes de eliminarlo.', 'role_in_use');
        }

        $role->delete();
    }
}
