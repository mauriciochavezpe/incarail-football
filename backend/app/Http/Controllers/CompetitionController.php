<?php

namespace App\Http\Controllers;

use App\Http\Requests\Football\ShowCompetitionRequest;
use App\Http\Resources\CompetitionResource;
use App\Services\Football\CompetitionCatalogService;
use App\Services\Football\CompetitionImportService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CompetitionController extends Controller
{
    /** GET /competitions */
    public function index(CompetitionCatalogService $catalog): AnonymousResourceCollection
    {
        [$competitions, $source] = $catalog->list();

        return CompetitionResource::collection($competitions)->additional(['meta' => $source->toArray()]);
    }

    /** GET /competitions/{competition_id} — importa equipos y jugadores si hace falta. */
    public function show(ShowCompetitionRequest $request, CompetitionImportService $importer): CompetitionResource
    {
        [$competition, $source] = $importer->show(
            $request->validated('competition_id'),
            canImport: $request->user()->hasPermission('import', 'Competition'),
        );

        return (new CompetitionResource($competition))->additional(['meta' => $source->toArray()]);
    }
}
