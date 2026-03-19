<?php

namespace App\Services\Tournament;

use App\Jobs\SeedTournamentRoundJob;
use App\Models\Tournament;
use Illuminate\Support\Collection;

class TournamentRoundSeedingService
{
    public function __construct(
        private readonly TournamentBracketConfigService $tournamentBracketConfigService,
        private readonly TournamentLudoMatchLinkService $tournamentLudoMatchLinkService
    ) {
    }

    public function dispatch(Tournament $tournament, int $roundNo = 1, ?int $tableSize = null): void
    {
        SeedTournamentRoundJob::dispatch($tournament->id, $roundNo, $tableSize);
    }

    public function seedRound(Tournament $tournament, int $roundNo = 1, ?int $tableSize = null): Collection
    {
        $resolvedTableSize = $tableSize ?: $this->tournamentBracketConfigService->resolveMatchSize($tournament);

        $existingMatches = $tournament->matches()
            ->where('round_no', $roundNo)
            ->count();

        if ($existingMatches > 0) {
            return $tournament->matchLinks()
                ->where('round_no', $roundNo)
                ->orderBy('table_no')
                ->orderBy('id')
                ->get();
        }

        if ($roundNo === 1) {
            return $this->tournamentLudoMatchLinkService->seedRoundOne($tournament, $resolvedTableSize);
        }

        $entries = $tournament->entries()
            ->where('status', 'winner')
            ->orderBy('entry_no')
            ->get();

        return $this->tournamentLudoMatchLinkService->seedRound(
            $tournament,
            $entries,
            $roundNo,
            $resolvedTableSize
        );
    }
}
