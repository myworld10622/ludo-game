<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Tournament\ClaimTournamentRoomRequest;
use App\Http\Resources\Api\V1\TournamentRoomResource;
use App\Models\Tournament;
use App\Models\TournamentEntry;
use App\Services\Tournament\TournamentLudoExecutionService;
use Illuminate\Http\JsonResponse;

class TournamentRoomController extends Controller
{
    public function __construct(
        private readonly TournamentLudoExecutionService $tournamentLudoExecutionService
    ) {
    }

    public function claim(ClaimTournamentRoomRequest $request, Tournament $tournament): JsonResponse
    {
        $entry = TournamentEntry::query()
            ->where('uuid', $request->validated()['tournament_entry_uuid'])
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $room = $this->tournamentLudoExecutionService->claimRoomForEntry($tournament, $entry);

        return response()->json([
            'success' => true,
            'data' => new TournamentRoomResource($room),
        ]);
    }
}
