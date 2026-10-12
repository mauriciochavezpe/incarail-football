<?php

namespace App\Services\Access;

use App\Exceptions\DomainRuleException;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class UserService
{
    /** @param array{name: string, email: string, password: string, roles: list<int>, is_active?: bool} $data */
    public function create(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => $data['name'],
                'email' => mb_strtolower($data['email']),
                'password' => $data['password'],          // el cast "hashed" la guarda con bcrypt
                'is_active' => $data['is_active'] ?? true,
            ]);
            $user->roles()->sync($data['roles']);

            return $user->load('roles');
        });
    }

    /** @param array<string, mixed> $data */
    public function update(User $user, array $data, User $actor): User
    {
        if ($user->is($actor) && array_key_exists('is_active', $data) && ! $data['is_active']) {
            throw new DomainRuleException('No puedes desactivar tu propio usuario.', 'cannot_deactivate_self');
        }

        return DB::transaction(function () use ($user, $data) {
            $fields = collect($data)->only(['name', 'email', 'is_active'])->all();
            if (isset($fields['email'])) {
                $fields['email'] = mb_strtolower($fields['email']);
            }
            if (! empty($data['password'])) {
                $fields['password'] = $data['password'];
            }
            $user->fill($fields)->save();

            if (array_key_exists('roles', $data)) {
                $user->roles()->sync($data['roles']);
            }

            // Si pierde el acceso, sus tokens dejan de servir de inmediato.
            if ($user->wasChanged('is_active') && ! $user->is_active) {
                $user->revokeAllTokens();
            }

            return $user->load('roles');
        });
    }

    public function setActive(User $user, bool $active, User $actor): User
    {
        return $this->update($user, ['is_active' => $active], $actor);
    }
}
