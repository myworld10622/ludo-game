<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\TournamentEntryResultResource;
use App\Http\Resources\Admin\TournamentMatchResource;
use App\Http\Resources\Admin\TournamentMatchLinkResource;
use App\Models\Tournament;
use App\Services\Tournament\TournamentHealthService;
use Illuminate\Http\JsonResponse;

class TournamentReportController extends Controller
{
    public function __construct(
        private readonly TournamentHealthService $tournamentHealthService
    ) {
    }

    public function leaderboard(Tournament $tournament): JsonResponse
    {
        $results = $tournament->results()
            ->with('entry')
            ->orderByRaw('CASE WHEN final_rank IS NULL THEN 999999 ELSE final_rank END')
            ->orderByDesc('score')
            ->paginate(100);

        return response()->json([
            'success' => true,
            'data' => TournamentEntryResultResource::collection($results),
        ]);
    }

    public function matchLinks(Tournament $tournament): JsonResponse
    {
        $links = $tournament->matchLinks()
            ->with('entry')
            ->orderBy('round_no')
            ->orderBy('table_no')
            ->paginate(200);

        return response()->json([
            'success' => true,
            'data' => TournamentMatchLinkResource::collection($links),
        ]);
    }

    public function matches(Tournament $tournament): JsonResponse
    {
        $matches = $tournament->matches()
            ->with('entries.tournamentEntry')
            ->orderBy('round_no')
            ->orderBy('match_no')
            ->paginate(200);

        return response()->json([
            'success' => true,
            'data' => TournamentMatchResource::collection($matches),
        ]);
    }

    public function health(Tournament $tournament): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->tournamentHealthService->summarize($tournament),
        ]);
    }
}
