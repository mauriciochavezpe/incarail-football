<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CompetitionController;
use App\Http\Controllers\PlayerController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

/*
| Las rutas del enunciado se respetan tal cual, sin prefijo /api
| (ver apiPrefix en bootstrap/app.php).
*/

// Única ruta pública de negocio (además de /up, el health check de Laravel).
Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:login');

Route::middleware(['auth:api', 'active', 'throttle:api'])->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);

    // --- Los cinco endpoints del enunciado ---
    Route::get('/competitions', [CompetitionController::class, 'index'])->middleware('permission:read,Competition');
    Route::get('/competitions/{competition_id}', [CompetitionController::class, 'show'])->middleware('permission:read,Competition');
    Route::get('/team', [TeamController::class, 'index'])->middleware('permission:read,Team');
    Route::get('/team/{team_id}', [TeamController::class, 'show'])->whereNumber('team_id')->middleware('permission:read,Team');
    Route::get('/players', [PlayerController::class, 'index'])->middleware('permission:read,Player');
    // Extra (no pedido en el enunciado): detalle de un jugador, para la pantalla de detalle.
    Route::get('/players/{player_id}', [PlayerController::class, 'show'])->whereNumber('player_id')->middleware('permission:read,Player');

    // --- Usuarios, roles y permisos ---
    Route::get('/users', [UserController::class, 'index'])->middleware('permission:read,User');
    Route::post('/users', [UserController::class, 'store'])->middleware('permission:create,User');
    Route::get('/users/{user}', [UserController::class, 'show'])->whereNumber('user')->middleware('permission:read,User');
    Route::match(['put', 'patch'], '/users/{user}', [UserController::class, 'update'])->whereNumber('user')->middleware('permission:update,User');
    Route::patch('/users/{user}/deactivate', [UserController::class, 'deactivate'])->whereNumber('user')->middleware('permission:update,User');
    Route::patch('/users/{user}/activate', [UserController::class, 'activate'])->whereNumber('user')->middleware('permission:update,User');

    Route::get('/roles', [RoleController::class, 'index'])->middleware('permission:read,Role');
    Route::post('/roles', [RoleController::class, 'store'])->middleware('permission:create,Role');
    Route::get('/roles/{role}', [RoleController::class, 'show'])->whereNumber('role')->middleware('permission:read,Role');
    Route::match(['put', 'patch'], '/roles/{role}', [RoleController::class, 'update'])->whereNumber('role')->middleware('permission:update,Role');
    Route::delete('/roles/{role}', [RoleController::class, 'destroy'])->whereNumber('role')->middleware('permission:delete,Role');
    Route::get('/permissions', [RoleController::class, 'permissions'])->middleware('permission:read,Role');
});
