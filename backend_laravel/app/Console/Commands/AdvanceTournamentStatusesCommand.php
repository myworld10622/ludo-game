<?php

namespace App\Console\Commands;

use App\Services\Tournament\TournamentStatusAutomationService;
use Illuminate\Console\Command;

class AdvanceTournamentStatusesCommand extends Command
{
    protected $signature = 'tournaments:advance-statuses';

    protected $description = 'Time-based tournament status transitions: draft→registration_open, registration_open→registration_closed.';

    public function handle(TournamentStatusAutomationService $service): int
    {
        $result = $service->advanceStatuses();

        $this->info('[TournamentScheduler] Status automation complete.');
        $this->line('  draft → registration_open  : ' . $result['opened_registration']);
        $this->line('  registration_open → closed : ' . $result['closed_registration']);
        $this->line('  missed-slot disqualified   : ' . ($result['disqualified_no_show'] ?? 0));

        return self::SUCCESS;
    }
}
