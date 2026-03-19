<?php

namespace App\Http\Controllers\Admin\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\V1\TournamentStoreRequest;
use App\Http\Requests\Admin\V1\TournamentUpdateRequest;
use App\Http\Resources\Api\V1\TournamentResource;
use App\Models\Tournament;
use App\Services\Tournament\TournamentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TournamentController extends Controller
{
    public function __construct(
        protected TournamentService $tournamentService
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $tournaments = $this->tournamentService->adminList($request->integer('per_page', 20));

        return $this->successResponse([
            'items' => TournamentResource::collection($tournaments->items())->resolve(),
            'pagination' => [
                'current_page' => $tournaments->currentPage(),
                'last_page' => $tournaments->lastPage(),
                'per_page' => $tournaments->perPage(),
                'total' => $tournaments->total(),
            ],
        ], 'Admin tournaments fetched successfully.');
    }

    public function store(TournamentStoreRequest $request): JsonResponse
    {
        $tournament = $this->tournamentService->create($request->validated());

        return $this->successResponse(
            new TournamentResource($tournament),
            'Tournament created successfully.',
            201
        );
    }

    public function update(TournamentUpdateRequest $request, Tournament $tournament): JsonResponse
    {
        $tournament = $this->tournamentService->update($tournament, $request->validated());

        return $this->successResponse(
            new TournamentResource($tournament),
            'Tournament updated successfully.'
        );
    }
}
