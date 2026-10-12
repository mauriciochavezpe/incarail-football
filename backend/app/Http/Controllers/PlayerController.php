<?php

namespace App\Http\Controllers;

use App\Http\Requests\Football\ListPlayersRequest;
use App\Http\Resources\PlayerResource;
use App\Models\Player;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/** Solo lee la base local: funciona aunque football-data no esté disponible. */
class PlayerController extends Controller
{
    /** GET /players */
    public function index(ListPlayersRequest $request): AnonymousResourceCollection
    {
        $players = Player::query()
            ->filter($request->filters())
            ->with('team')
            ->orderBy('name')
            ->paginate($request->integer('per_page', 25))
            ->withQueryString();

        return PlayerResource::collection($players);
    }

    /** GET /players/{player_id} */
    public function show(int $playerId): PlayerResource
    {
        return new PlayerResource(Player::query()->with('team.competitions')->findOrFail($playerId));
    }
}
