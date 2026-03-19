<?php

namespace App\Jobs;

use App\Models\Tournament;
use App\Services\Tournament\TournamentRoundLifecycleService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RunTournamentRoundLifecycleJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly int $tournamentId,
        public readonly int $roundNo = 1,
        public readonly ?int $tableSize = null
    ) {
    }

    public int $uniqueFor = 300;

    public function uniqueId(): string
    {
        return sprintf('tournament-round-lifecycle:%d:%d', $this->tournamentId, $this->roundNo);
    }

    public function handle(TournamentRoundLifecycleService $tournamentRoundLifecycleService): void
    {
        $tournament = Tournament::query()->with('game')->findOrFail($this->tournamentId);

        $tournamentRoundLifecycleService->runRound(
            $tournament,
            $this->roundNo,
            $this->tableSize
        );
    }
}
