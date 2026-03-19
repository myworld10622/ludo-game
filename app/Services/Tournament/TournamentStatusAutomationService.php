<?php

namespace App\Services\Tournament;

use App\Models\Tournament;
use Carbon\CarbonImmutable;

class TournamentStatusAutomationService
{
    public function __construct(
        private readonly TournamentRoundLifecycleService $tournamentRoundLifecycleService
    ) {
    }

    public function advanceStatuses(): array
    {
        $now = CarbonImmutable::now();
        $movedToEntryOpen = 0;
        $movedToEntryLocked = 0;
        $movedToSeeding = 0;

        Tournament::query()
            ->where('status', 'published')
            ->where(function ($query) use ($now) {
                $query->whereNull('entry_open_at')
                    ->orWhere('entry_open_at', '<=', $now);
            })
            ->chunkById(100, function ($tournaments) use (&$movedToEntryOpen) {
                foreach ($tournaments as $tournament) {
                    $tournament->status = 'entry_open';
                    $tournament->save();
                    $movedToEntryOpen++;
                }
            });

        Tournament::query()
            ->where('status', 'entry_open')
            ->whereNotNull('entry_close_at')
            ->where('entry_close_at', '<=', $now)
            ->chunkById(100, function ($tournaments) use (&$movedToEntryLocked) {
                foreach ($tournaments as $tournament) {
                    $tournament->status = 'entry_locked';
                    $tournament->save();
                    $movedToEntryLocked++;
                }
            });

        Tournament::query()
            ->where('status', 'entry_locked')
            ->whereNotNull('start_at')
            ->where('start_at', '<=', $now)
            ->chunkById(100, function ($tournaments) use (&$movedToSeeding) {
                foreach ($tournaments as $tournament) {
                    $tournament->status = 'seeding';
                    $tournament->save();
                    $this->tournamentRoundLifecycleService->dispatch($tournament, 1);
                    $movedToSeeding++;
                }
            });

        return [
            'entry_open' => $movedToEntryOpen,
            'entry_locked' => $movedToEntryLocked,
            'seeding' => $movedToSeeding,
        ];
    }
}
