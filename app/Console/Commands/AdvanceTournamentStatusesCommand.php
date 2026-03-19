<?php

namespace App\Console\Commands;

use App\Services\Tournament\TournamentStatusAutomationService;
use Illuminate\Console\Command;

class AdvanceTournamentStatusesCommand extends Command
{
    protected $signature = 'tournaments:advance-statuses';

    protected $description = 'Advance tournament statuses based on configured timings.';

    public function handle(TournamentStatusAutomationService $service): int
    {
        $result = $service->advanceStatuses();

        $this->info('Tournament status automation complete.');
        $this->line('Moved to entry_open: '.$result['entry_open']);
        $this->line('Moved to entry_locked: '.$result['entry_locked']);
        $this->line('Moved to seeding: '.$result['seeding']);

        return self::SUCCESS;
    }
}
