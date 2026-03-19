<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\GameCollection;
use App\Http\Resources\Api\V1\HomeResource;
use App\Services\Games\GameCatalogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GameController extends Controller
{
    public function __construct(
        protected GameCatalogService $gameCatalogService
    ) {
    }

    public function index(): JsonResponse
    {
        return $this->successResponse(
            new GameCollection($this->gameCatalogService->visibleGames()),
            'Games fetched successfully.'
        );
    }

    public function home(Request $request): JsonResponse
    {
        return $this->successResponse(
            new HomeResource($this->gameCatalogService->homePayload($request->user())),
            'Home data fetched successfully.'
        );
    }
}
