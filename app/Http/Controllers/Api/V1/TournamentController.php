<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Tournament\JoinTournamentRequest;
use App\Http\Resources\Api\V1\TournamentEntryResource;
use App\Http\Resources\Api\V1\TournamentResource;
use App\Models\Tournament;
use App\Services\Tournament\TournamentJoinService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TournamentController extends Controller
{
    public function __construct(
        private readonly TournamentJoinService $tournamentJoinService
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $includeNonJoinable = $request->boolean('include_non_joinable', false);
        $statuses = $includeNonJoinable
            ? ['published', 'entry_open', 'entry_locked', 'running']
            : ['published', 'entry_open'];
        $now = now();

        $query = Tournament::query()
            ->with(['game', 'prizes'])
            ->whereIn('status', $statuses)
            ->when(! $includeNonJoinable, function ($query) use ($now) {
                $query
                    ->where(function ($inner) use ($now) {
                        $inner->whereNull('entry_open_at')
                            ->orWhere('entry_open_at', '<=', $now);
                    })
                    ->where(function ($inner) use ($now) {
                        $inner->whereNull('entry_close_at')
                            ->orWhere('entry_close_at', '>=', $now);
                    });
            })
            ->latest();

        if ($request->filled('game_id')) {
            $query->where('game_id', $request->integer('game_id'));
        }

        return response()->json([
            'success' => true,
            'data' => TournamentResource::collection($query->paginate(20)),
        ]);
    }

    public function show(Tournament $tournament): JsonResponse
    {
        $tournament->load(['game', 'prizes']);

        return response()->json([
            'success' => true,
            'data' => new TournamentResource($tournament),
        ]);
    }

    public function join(JoinTournamentRequest $request, Tournament $tournament): JsonResponse
    {
        $entries = $this->tournamentJoinService->join(
            $tournament,
            $request->user(),
            (int) ($request->validated()['entries'] ?? 1)
        );

        $primaryEntry = $entries->first();

        return response()->json([
            'success' => true,
            'message' => 'Tournament joined successfully.',
            'entry_uuid' => $primaryEntry?->uuid ?? $primaryEntry?->entry_uuid,
            'tournament_entry_uuid' => $primaryEntry?->uuid ?? $primaryEntry?->entry_uuid,
            'data' => TournamentEntryResource::collection($entries),
        ], 201);
    }
}
