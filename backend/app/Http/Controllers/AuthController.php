<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\UserResource;
use App\Services\Access\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(private readonly AuthService $auth) {}

    public function login(LoginRequest $request): JsonResponse
    {
        $session = $this->auth->login($request->validated('email'), $request->validated('password'));

        return response()->json([
            'data' => [
                'token_type' => 'Bearer',
                'access_token' => $session['access_token'],
                'expires_at' => $session['expires_at']?->toIso8601String(),
                'user' => new UserResource($session['user']),
                'rules' => $session['user']->abilityRules(),
            ],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $this->auth->logout($request->user());

        return response()->json(null, 204);
    }

    /** Usuario actual + reglas para CASL: [{ action, subject }]. */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load('roles');

        return response()->json([
            'data' => [
                'user' => new UserResource($user),
                'rules' => $user->abilityRules(),
            ],
        ]);
    }
}
