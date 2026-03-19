<?php

namespace App\Console\Commands;

use App\Models\Game;
use App\Models\Tournament;
use App\Models\User;
use App\Services\Tournament\TournamentJoinService;
use App\Services\Tournament\TournamentLudoExecutionService;
use App\Services\Tournament\TournamentLudoMatchLinkService;
use App\Services\Tournament\TournamentLudoRoomProvisionService;
use App\Services\Tournament\TournamentSettlementService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class SmokeTestTournamentRuntimeCommand extends Command
{
    protected $signature = 'tournaments:smoke-runtime
        {--match-size=2 : Players per match, supported values are 2 and 4}
        {--entries=4 : Number of tournament entries to simulate}
        {--seeding-strategy=random : Seeding strategy to use}
        {--user-id= : Optional user id to force for all entries}';

    protected $description = 'Run a backend-only tournament runtime smoke test using the new TournamentMatch primary flow.';

    public function handle(
        TournamentJoinService $tournamentJoinService,
        TournamentLudoMatchLinkService $tournamentLudoMatchLinkService,
        TournamentLudoRoomProvisionService $tournamentLudoRoomProvisionService,
        TournamentLudoExecutionService $tournamentLudoExecutionService,
        TournamentSettlementService $tournamentSettlementService
    ): int {
        $matchSize = (int) $this->option('match-size');
        $matchSize = in_array($matchSize, [2, 4], true) ? $matchSize : 2;
        $entriesTarget = max($matchSize, (int) $this->option('entries'));
        $seedingStrategy = (string) $this->option('seeding-strategy');
        $seedingStrategy = in_array($seedingStrategy, ['random', 'ranked', 'segmented'], true)
            ? $seedingStrategy
            : 'random';
        $userId = $this->option('user-id');

        $game = Game::query()
            ->where(function ($query) {
                $query
                    ->where('code', 'ludo')
                    ->orWhere('slug', 'ludo')
                    ->orWhere('name', 'like', '%ludo%');
            })
            ->first();

        if (! $game) {
            $this->error('Ludo game record not found.');
            return self::FAILURE;
        }

        $user = $userId
            ? User::query()->find($userId)
            : User::query()->orderBy('id')->first();

        if (! $user) {
            $this->error('No user found for tournament smoke test.');
            return self::FAILURE;
        }

        $now = Carbon::now();
        $tournament = Tournament::query()->create([
            'uuid' => (string) Str::uuid(),
            'game_id' => $game->id,
            'slug' => 'smoke-runtime-' . Str::lower(Str::random(8)),
            'name' => 'Smoke Runtime Tournament',
            'code' => 'SMOKE-' . strtoupper(Str::random(6)),
            'type' => 'standard',
            'status' => 'published',
            'currency' => 'chips',
            'entry_fee' => 0,
            'allow_multiple_entries' => true,
            'max_entries_per_user' => $entriesTarget,
            'min_total_entries' => $matchSize,
            'max_total_entries' => $entriesTarget,
            'match_size' => $matchSize,
            'advance_count' => 1,
            'bracket_size' => null,
            'bye_count' => 0,
            'seeding_strategy' => $seedingStrategy,
            'bot_fill_policy' => 'fill_after_timeout',
            'ticket_prefix' => 'SMOKE',
            'next_entry_no' => 1,
            'current_total_entries' => 0,
            'current_active_entries' => 0,
            'entry_open_at' => $now->copy()->subMinute(),
            'entry_close_at' => $now->copy()->addHour(),
            'start_at' => $now->copy()->addMinutes(30),
            'rules' => [
                'game_mode' => 'CLASSIC',
                'players_per_match' => $matchSize,
                'advance_count' => 1,
                'seeding_strategy' => $seedingStrategy,
            ],
            'meta' => [
                'max_players' => $matchSize,
                'unity_test' => false,
                'backend_smoke' => true,
            ],
        ]);

        $entries = $tournamentJoinService->join($tournament, $user, $entriesTarget);
        $tournament = $tournament->fresh();

        $tournamentLudoMatchLinkService->seedRoundOne($tournament, $matchSize);
        $tournament = $tournament->fresh();

        $bootstrapEntry = $this->resolveActiveEntriesForRound($tournament, 1)->first();
        if ($bootstrapEntry) {
            $tournamentLudoExecutionService->claimRoomForEntry($tournament, $bootstrapEntry->fresh());
            $tournament = $tournament->fresh();
        }

        $round = 1;

        while ($tournament->status !== 'completed') {
            $activeEntries = $this->resolveActiveEntriesForRound($tournament, $round);
            if ($activeEntries->isEmpty()) {
                break;
            }

            $sampleEntry = $activeEntries->first();
            $claimedRoom = $tournamentLudoExecutionService->claimRoomForEntry($tournament, $sampleEntry);
            $rooms = $tournamentLudoRoomProvisionService->provisionRoomsForRound($tournament->fresh('game'), $round, $matchSize);

            foreach ($rooms as $room) {
                $players = $room->players()->orderBy('seat_no')->get();
                $winnerSeat = (int) $players->first()->seat_no;

                $rankings = $players->map(function ($player, $index) use ($winnerSeat) {
                    return [
                        'tournament_entry_id' => (int) data_get($player->meta, 'tournament_entry_id'),
                        'final_rank' => (int) ($player->seat_no === $winnerSeat ? 1 : ($index + 2)),
                        'score' => (float) ($player->seat_no === $winnerSeat ? 1 : 0),
                    ];
                })->values()->all();

                $settlementTournament = $tournamentLudoExecutionService->completeProvisionedRoom($room->fresh('players'), $rankings);
                $tournament = $tournamentSettlementService->settle($settlementTournament, $rankings)->fresh();
            }

            $this->line(sprintf(
                'Round %d complete. Claimed room: %s. Tournament status: %s',
                $round,
                $claimedRoom->room_uuid,
                $tournament->status
            ));

            $round++;
        }

        $tournament = $tournament->fresh();

        $this->info('Tournament runtime smoke test completed.');
        $this->line('Tournament UUID: ' . $tournament->uuid);
        $this->line('Final status: ' . $tournament->status);
        $this->line('Entries created: ' . $entries->count());

        return $tournament->status === 'completed' ? self::SUCCESS : self::FAILURE;
    }

    private function resolveActiveEntriesForRound(Tournament $tournament, int $roundNo): Collection
    {
        $matchEntryIds = $tournament->matches()
            ->where('round_no', $roundNo)
            ->with('entries')
            ->get()
            ->pluck('entries')
            ->flatten(1)
            ->where('status', '!=', 'completed')
            ->pluck('tournament_entry_id')
            ->unique()
            ->values();

        if ($matchEntryIds->isEmpty()) {
            return collect();
        }

        return $tournament->entries()
            ->whereIn('id', $matchEntryIds->all())
            ->orderBy('entry_no')
            ->get();
    }
}
