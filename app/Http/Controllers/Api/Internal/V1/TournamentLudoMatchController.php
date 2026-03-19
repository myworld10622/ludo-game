<?php

namespace App\Http\Controllers\Api\Internal\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Internal\V1\Tournament\CompleteTournamentMatchRequest;
use App\Http\Resources\Admin\TournamentResource;
use App\Models\GameRoom;
use App\Services\Tournament\TournamentLudoExecutionService;
use App\Services\Tournament\TournamentSettlementService;
use Illuminate\Http\JsonResponse;

class TournamentLudoMatchController extends Controller
{
    public function __construct(
        private readonly TournamentLudoExecutionService $tournamentLudoExecutionService,
        private readonly TournamentSettlementService $tournamentSettlementService
    ) {
    }

    public function complete(CompleteTournamentMatchRequest $request, string $roomUuid): JsonResponse
    {
        $room = GameRoom::query()
            ->with('players')
            ->where('room_uuid', $roomUuid)
            ->firstOrFail();

        $tournament = $this->tournamentLudoExecutionService->completeProvisionedRoom(
            $room,
            $request->validated()['rankings']
        );

        $tournament = $this->tournamentSettlementService->settle(
            $tournament,
            $request->validated()['rankings']
        );

        return response()->json([
            'success' => true,
            'message' => 'Tournament Ludo match completed successfully.',
            'data' => new TournamentResource($tournament),
        ]);
    }
}
