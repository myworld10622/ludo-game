<?php

namespace App\Http\Controllers\Admin\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\V1\GameStoreRequest;
use App\Http\Requests\Admin\V1\GameUpdateRequest;
use App\Http\Resources\Api\V1\GameCollection;
use App\Http\Resources\Api\V1\GameResource;
use App\Models\Game;
use App\Services\Games\GameCatalogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GameController extends Controller
{
    public function __construct(
        protected GameCatalogService $gameCatalogService
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $games = $this->gameCatalogService->listAdminGames((int) $request->integer('per_page', 20));

        return $this->successResponse(
            [
                'items' => GameResource::collection($games->items())->resolve(),
                'pagination' => [
                    'current_page' => $games->currentPage(),
                    'last_page' => $games->lastPage(),
                    'per_page' => $games->perPage(),
                    'total' => $games->total(),
                ],
            ],
            'Admin game list fetched successfully.'
        );
    }

    public function store(GameStoreRequest $request): JsonResponse
    {
        $game = $this->gameCatalogService->createGame($request->validated());

        return $this->successResponse(
            new GameResource($game),
            'Game created successfully.',
            201
        );
    }

    public function update(GameUpdateRequest $request, Game $game): JsonResponse
    {
        $game = $this->gameCatalogService->updateGame($game, $request->validated());

        return $this->successResponse(
            new GameResource($game),
            'Game updated successfully.'
        );
    }
}
