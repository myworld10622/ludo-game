<?php

namespace App\Http\Controllers\Api\Internal\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Internal\V1\Ludo\CompleteLudoMatchRequest;
use App\Http\Requests\Api\Internal\V1\Ludo\StartLudoMatchRequest;
use App\Http\Resources\Api\V1\GameMatchResource;
use App\Models\GameMatch;
use App\Models\GameRoom;
use App\Models\TournamentMatch;
use App\Services\Match\LudoMatchLifecycleService;

class LudoMatchController extends Controller
{
    public function __construct(
        protected LudoMatchLifecycleService $matchLifecycleService
    ) {
    }

    public function start(StartLudoMatchRequest $request, string $roomUuid)
    {
        // Tournament room — roomUuid is "tournament-match-{id}"
        if (str_starts_with($roomUuid, 'tournament-match-')) {
            $matchId = (int) str_replace('tournament-match-', '', $roomUuid);
            $tournamentMatch = TournamentMatch::find($matchId);

            if ($tournamentMatch && $tournamentMatch->status !== TournamentMatch::STATUS_IN_PROGRESS) {
                $tournamentMatch->update([
                    'status'     => TournamentMatch::STATUS_IN_PROGRESS,
                    'started_at' => now(),
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Tournament match started.',
                'data'    => ['match_uuid' => $roomUuid],
            ]);
        }

        $room = GameRoom::query()
            ->with(['game', 'players.user'])
            ->where('room_uuid', $roomUuid)
            ->firstOrFail();

        $match = $this->matchLifecycleService->startMatch($room, $request->validated());

        return $this->successResponse(
            new GameMatchResource($match->loadMissing(['room', 'players.user'])),
            'Ludo match started successfully.'
        );
    }

    public function complete(CompleteLudoMatchRequest $request, string $matchUuid)
    {
        $match = GameMatch::query()
            ->with(['room.players.walletTransaction', 'players.user', 'players.roomPlayer.walletTransaction'])
            ->where('match_uuid', $matchUuid)
            ->firstOrFail();

        $payload = $request->validated();
        if (! empty($payload['result_payload']) && is_array($payload['result_payload'])) {
            $payload = array_merge($payload['result_payload'], $payload);
        }

        $completedMatch = $this->matchLifecycleService->completeMatch($match, $payload);

        return $this->successResponse(
            new GameMatchResource($completedMatch->loadMissing(['room', 'players.user', 'winner'])),
            $completedMatch->status === 'cancelled'
                ? 'Ludo match cancelled successfully.'
                : 'Ludo match settled successfully.'
        );
    }
}
