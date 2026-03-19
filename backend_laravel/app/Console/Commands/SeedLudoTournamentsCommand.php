<?php

namespace App\Console\Commands;

use App\Models\Tournament;
use App\Services\Tournament\TournamentRoundSeedingService;
use Illuminate\Console\Command;

class SeedLudoTournamentsCommand extends Command
{
    protected $signature = 'tournaments:seed-ludo {--queued : Dispatch jobs instead of seeding inline}';

    protected $description = 'Seed locked or seeding Ludo tournaments into tournament match links.';

    public function handle(TournamentRoundSeedingService $service): int
    {
        $seededCount = 0;
        $queued = (bool) $this->option('queued');

        Tournament::query()
            ->with('game')
            ->whereIn('status', ['entry_locked', 'seeding'])
            ->chunkById(100, function ($tournaments) use ($service, &$seededCount, $queued) {
                foreach ($tournaments as $tournament) {
                    if (($tournament->game->game ?? null) !== 'ludo') {
                        continue;
                    }

                    if ($tournament->matchLinks()->exists()) {
                        if ($tournament->status === 'seeding') {
                            $tournament->status = 'running';
                            $tournament->save();
                        }

                        continue;
                    }

                    if ($queued) {
                        $service->dispatch($tournament, 1);
                    } else {
                        $service->seedRound($tournament, 1);
                    }
                    $tournament->status = 'running';
                    $tournament->save();
                    $seededCount++;
                }
            });

        $this->info('Ludo tournament seeding complete.');
        $this->line('Tournaments seeded: '.$seededCount);

        return self::SUCCESS;
    }
}
