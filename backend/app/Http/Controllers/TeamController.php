<?php

namespace App\Http\Controllers;

use App\Http\Requests\Football\ListTeamsRequest;
use App\Http\Resources\TeamResource;
use App\Models\Team;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/** Solo lee la base local: funciona aunque football-data no esté disponible. */
class TeamController extends Controller
{
    /** GET /team */
    public function index(ListTeamsRequest $request): AnonymousResourceCollection
    {
        $teams = Team::query()
            ->filter($request->filters())
            ->withCount('players')
            ->with('competitions')
            ->orderBy('name')
            ->paginate($request->integer('per_page', 24))
            ->withQueryString();

        return TeamResource::collection($teams);
    }

    /** GET /team/{team_id} */
    public function show(int $teamId): TeamResource
    {
        $team = Team::query()
            ->with(['competitions', 'players' => fn ($q) => $q->orderBy('position')->orderBy('name')])
            ->withCount('players')
            ->findOrFail($teamId);

        return new TeamResource($team);
    }
}
