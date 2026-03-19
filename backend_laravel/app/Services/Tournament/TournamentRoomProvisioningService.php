<?php

namespace App\Services\Tournament;

use App\Jobs\ProvisionTournamentRoundRoomsJob;
use App\Models\Tournament;
use Illuminate\Support\Collection;

class TournamentRoomProvisioningService
{
    public function __construct(
        private readonly TournamentBracketConfigService $tournamentBracketConfigService,
        private readonly TournamentLudoRoomProvisionService $tournamentLudoRoomProvisionService
    ) {
    }

    public function dispatch(Tournament $tournament, int $roundNo = 1, ?int $tableSize = null): void
    {
        ProvisionTournamentRoundRoomsJob::dispatch($tournament->id, $roundNo, $tableSize);
    }

    public function provisionRound(Tournament $tournament, int $roundNo = 1, ?int $tableSize = null): Collection
    {
        $resolvedTableSize = $tableSize ?: $this->tournamentBracketConfigService->resolveMatchSize($tournament);

        $alreadyProvisioned = $tournament->matches()
            ->where('round_no', $roundNo)
            ->whereNotNull('external_match_ref')
            ->exists();

        if ($alreadyProvisioned) {
            return $this->tournamentLudoRoomProvisionService->provisionRoomsForRound(
                $tournament,
                $roundNo,
                $resolvedTableSize
            );
        }

        return $this->tournamentLudoRoomProvisionService->provisionRoomsForRound(
            $tournament,
            $roundNo,
            $resolvedTableSize
        );
    }
}
