<?php

namespace App\Http\Controllers;

use App\Http\Requests\Users\ListUsersRequest;
use App\Http\Requests\Users\StoreUserRequest;
use App\Http\Requests\Users\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\Access\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class UserController extends Controller
{
    public function __construct(private readonly UserService $users) {}

    public function index(ListUsersRequest $request): AnonymousResourceCollection
    {
        $users = User::query()
            ->with('roles')
            ->search($request->validated('search'))
            ->orderBy('name')
            ->paginate($request->integer('per_page', 20))
            ->withQueryString();

        return UserResource::collection($users);
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        return (new UserResource($this->users->create($request->validated())))
            ->response()
            ->setStatusCode(201);
    }

    public function show(User $user): UserResource
    {
        return new UserResource($user->load('roles'));
    }

    public function update(UpdateUserRequest $request, User $user): UserResource
    {
        return new UserResource($this->users->update($user, $request->validated(), $request->user()));
    }

    public function deactivate(Request $request, User $user): UserResource
    {
        return new UserResource($this->users->setActive($user, false, $request->user()));
    }

    public function activate(Request $request, User $user): UserResource
    {
        return new UserResource($this->users->setActive($user, true, $request->user()));
    }
}
