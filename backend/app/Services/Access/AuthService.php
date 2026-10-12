<?php

namespace App\Services\Access;

use App\Exceptions\AccountDisabledException;
use App\Models\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Hash;
use Laravel\Passport\PersonalAccessTokenResult;

class AuthService
{
    /**
     * Valida credenciales y emite un token de acceso personal de Passport.
     *
     * @throws AuthenticationException credenciales inválidas (401)
     * @throws AccountDisabledException usuario desactivado (403)
     */
    public function login(string $email, string $password): array
    {
        $user = User::query()->where('email', mb_strtolower($email))->first();

        // Mismo mensaje para "no existe" y "contraseña incorrecta": no revelamos qué correos existen.
        if (! $user || ! Hash::check($password, $user->password)) {
            throw new AuthenticationException('Correo o contraseña incorrectos.');
        }

        if (! $user->is_active) {
            throw new AccountDisabledException;
        }

        /** @var PersonalAccessTokenResult $result */
        $result = $user->createToken('spa');
        $user->forceFill(['last_login_at' => now()])->save();

        return [
            'user' => $user->load('roles'),
            'access_token' => $result->accessToken,
            'expires_at' => $result->getToken()?->expires_at,
        ];
    }

    public function logout(User $user): void
    {
        $user->token()?->revoke();
    }
}
