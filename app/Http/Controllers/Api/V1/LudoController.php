<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Ludo\JoinLudoQueueRequest;
use App\Http\Resources\Api\V1\GameRoomResource;
use App\Services\Match\LudoMatchmakingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LudoController extends Controller
{
    public function __construct(
        protected LudoMatchmakingService $matchmakingService
    ) {
    }

    public function joinQueue(JoinLudoQueueRequest $request): JsonResponse
    {
        $room = $this->matchmakingService->joinPublicQueue($request->user(), $request->validated());

        return $this->successResponse(
            new GameRoomResource($room),
            'Ludo room joined successfully.'
        );
    }

    public function room(Request $request, string $roomUuid): JsonResponse
    {
        $room = $this->matchmakingService->getRoomSnapshot($roomUuid);

        return $this->successResponse(
            new GameRoomResource($room),
            'Ludo room fetched successfully.'
        );
    }
}
