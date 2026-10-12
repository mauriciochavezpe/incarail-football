<?php

namespace App\Http\Controllers;

use App\Http\Requests\Roles\StoreRoleRequest;
use App\Http\Requests\Roles\UpdateRoleRequest;
use App\Http\Resources\PermissionResource;
use App\Http\Resources\RoleResource;
use App\Models\Permission;
use App\Models\Role;
use App\Services\Access\RoleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class RoleController extends Controller
{
    public function __construct(private readonly RoleService $roles) {}

    public function index(): AnonymousResourceCollection
    {
        return RoleResource::collection(Role::query()->with('permissions')->withCount('users')->orderBy('id')->get());
    }

    public function store(StoreRoleRequest $request): JsonResponse
    {
        return (new RoleResource($this->roles->create($request->validated())))->response()->setStatusCode(201);
    }

    public function show(Role $role): RoleResource
    {
        return new RoleResource($role->load('permissions')->loadCount('users'));
    }

    public function update(UpdateRoleRequest $request, Role $role): RoleResource
    {
        return new RoleResource($this->roles->update($role, $request->validated()));
    }

    public function destroy(Role $role): JsonResponse
    {
        $this->roles->delete($role);

        return response()->json(null, 204);
    }

    /** Catálogo de permisos disponibles (para armar roles). */
    public function permissions(): AnonymousResourceCollection
    {
        return PermissionResource::collection(Permission::query()->orderBy('subject')->orderBy('action')->get());
    }
}
