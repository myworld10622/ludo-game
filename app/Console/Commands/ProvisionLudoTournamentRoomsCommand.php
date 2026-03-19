<?php

namespace App\Console\Commands;

use App\Models\Tournament;
use App\Services\Tournament\TournamentLudoRoomProvisionService;
use Illuminate\Console\Command;

class ProvisionLudoTournamentRoomsCommand extends Command
{
    protected $signature = 'tournaments:provision-ludo-rooms';

    protected $description = 'Provision round-one Ludo tournament rooms from seeded tournament match links.';

    public function handle(TournamentLudoRoomProvisionService $service): int
    {
        $provisioned = 0;

        Tournament::query()
            ->with('game')
            ->where('status', 'running')
            ->chunkById(100, function ($tournaments) use ($service, &$provisioned) {
                foreach ($tournaments as $tournament) {
                    if (($tournament->game->game ?? null) !== 'ludo') {
                        continue;
                    }

                    $rooms = $service->provisionRoundOneRooms($tournament);
                    if ($rooms->isNotEmpty()) {
                        $provisioned += $rooms->count();
                    }
                }
            });

        $this->info('Ludo tournament room provisioning complete.');
        $this->line('Rooms provisioned: '.$provisioned);

        return self::SUCCESS;
    }
}
