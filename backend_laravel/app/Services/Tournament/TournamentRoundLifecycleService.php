<?php

namespace App\Services\Tournament;

use App\Jobs\RunTournamentRoundLifecycleJob;
use App\Models\Tournament;

class TournamentRoundLifecycleService
{
    public function __construct(
        private readonly TournamentBracketConfigService $tournamentBracketConfigService,
        private readonly TournamentRoundSeedingService $tournamentRoundSeedingService,
        private readonly TournamentRoomProvisioningService $tournamentRoomProvisioningService,
        private readonly TournamentLifecycleLockService $tournamentLifecycleLockService
    ) {
    }

    public function dispatch(Tournament $tournament, int $roundNo = 1, ?int $tableSize = null): void
    {
        RunTournamentRoundLifecycleJob::dispatch($tournament->id, $roundNo, $tableSize);
    }

    public function runRound(Tournament $tournament, int $roundNo = 1, ?int $tableSize = null): void
    {
        $this->tournamentLifecycleLockService->withRoundLock($tournament->id, $roundNo, function () use ($tournament, $roundNo, $tableSize): void {
            $resolvedTableSize = $tableSize ?: $this->tournamentBracketConfigService->resolveMatchSize($tournament);

            $this->tournamentRoundSeedingService->seedRound(
                $tournament,
                $roundNo,
                $resolvedTableSize
            );

            $tournament = $tournament->fresh('game');
            $this->tournamentRoomProvisioningService->provisionRound(
                $tournament,
                $roundNo,
                $resolvedTableSize
            );

            if ($tournament->status !== 'completed') {
                $tournament->status = 'running';
                $tournament->save();
            }
        });
    }
}
