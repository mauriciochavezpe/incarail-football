<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Collection;
use Laravel\Passport\Contracts\OAuthenticatable;
use Laravel\Passport\HasApiTokens;

#[Fillable(['name', 'email', 'password', 'is_active'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements OAuthenticatable
{
    use HasApiTokens;

    /** Permisos ya resueltos (evita repetir la consulta en una misma petición). */
    private ?Collection $resolvedPermissions = null;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    /** Búsqueda por nombre o correo. */
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        $term = trim((string) $term);

        return $query->when($term !== '', fn (Builder $q) => $q->where(
            fn (Builder $q) => $q->whereLike('name', "%{$term}%")->orWhereLike('email', "%{$term}%")
        ));
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }

    /**
     * Permisos efectivos del usuario (unión de los permisos de sus roles).
     *
     * @return Collection<int, Permission>
     */
    public function permissions(): Collection
    {
        return $this->resolvedPermissions ??= Permission::query()
            ->whereHas('roles.users', fn ($q) => $q->whereKey($this->getKey()))
            ->orderBy('subject')
            ->orderBy('action')
            ->get();
    }

    public function hasPermission(string $action, string $subject): bool
    {
        return $this->permissions()->contains(
            fn (Permission $p) => $p->action === $action && $p->subject === $subject
        );
    }

    /**
     * Reglas en el formato que entiende CASL: [{ action, subject }].
     *
     * @return array<int, array{action: string, subject: string}>
     */
    public function abilityRules(): array
    {
        return $this->permissions()
            ->map(fn (Permission $p) => ['action' => $p->action, 'subject' => $p->subject])
            ->values()
            ->all();
    }

    /** Revoca todos los tokens de acceso (al desactivar o cambiar roles). */
    public function revokeAllTokens(): void
    {
        $this->tokens()->update(['revoked' => true]);
    }
}
