<?php

namespace App\Console\Commands;

use App\Models\Game;
use App\Models\Tournament;
use App\Models\TournamentPrize;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class CreateTestLudoTournamentCommand extends Command
{
    protected $signature = 'tournaments:create-test-ludo
        {--name=Test Ludo Tournament : Tournament display name}
        {--entry-fee=10 : Entry fee in chips}
        {--max-entries=16 : Maximum total entries}
        {--match-size=2 : Players per match, supported values are 2 and 4}
        {--max-entries-per-user=1 : Maximum tickets allowed per user}
        {--start-minutes=30 : Minutes from now when tournament starts}';

    protected $description = 'Create a published test Ludo tournament for Unity tournament flow testing.';

    public function handle(): int
    {
        $game = Game::query()
            ->where(function ($query) {
                $query
                    ->where('code', 'ludo')
                    ->orWhere('slug', 'ludo')
                    ->orWhere('name', 'like', '%ludo%');
            })
            ->first();

        if ($game === null) {
            $this->error('Ludo game record not found in games table.');
            return self::FAILURE;
        }

        $name = trim((string) $this->option('name'));
        $entryFee = (float) $this->option('entry-fee');
        $maxEntries = max(2, (int) $this->option('max-entries'));
        $matchSize = (int) $this->option('match-size');
        $matchSize = in_array($matchSize, [2, 4], true) ? $matchSize : 2;
        $maxEntriesPerUser = max(1, (int) $this->option('max-entries-per-user'));
        $startMinutes = max(1, (int) $this->option('start-minutes'));
        $now = Carbon::now();
        $startAt = $now->copy()->addMinutes($startMinutes);
        $entryCloseAt = $startAt->copy()->subMinutes(5);
        $slugBase = Str::slug($name);
        $uniqueSuffix = Str::lower(Str::random(6));

        $tournament = Tournament::query()->create([
            'uuid' => (string) Str::uuid(),
            'game_id' => $game->id,
            'slug' => $slugBase . '-' . $uniqueSuffix,
            'name' => $name,
            'code' => 'LUDO-' . strtoupper(Str::random(6)),
            'type' => 'standard',
            'status' => 'published',
            'currency' => 'chips',
            'entry_fee' => $entryFee,
            'allow_multiple_entries' => $maxEntriesPerUser > 1,
            'max_entries_per_user' => $maxEntriesPerUser,
            'min_total_entries' => $matchSize,
            'max_total_entries' => $maxEntries,
            'match_size' => $matchSize,
            'advance_count' => 1,
            'bracket_size' => null,
            'bye_count' => 0,
            'seeding_strategy' => 'random',
            'bot_fill_policy' => 'fill_after_timeout',
            'ticket_prefix' => 'LUDO',
            'next_entry_no' => 1,
            'current_total_entries' => 0,
            'current_active_entries' => 0,
            'entry_open_at' => $now,
            'entry_close_at' => $entryCloseAt,
            'start_at' => $startAt,
            'rules' => [
                'game_mode' => 'CLASSIC',
                'players_per_match' => $matchSize,
                'advance_count' => 1,
                'rounds' => 1,
            ],
            'meta' => [
                'max_players' => $matchSize,
                'advance_count' => 1,
                'joined_players' => 0,
                'unity_test' => true,
            ],
        ]);

        TournamentPrize::query()->create([
            'tournament_id' => $tournament->id,
            'rank_from' => 1,
            'rank_to' => 1,
            'prize_type' => 'chips',
            'prize_amount' => $entryFee * 2,
            'meta' => [
                'label' => 'Winner',
            ],
        ]);

        $this->info('Test Ludo tournament created successfully.');
        $this->line('UUID: ' . $tournament->uuid);
        $this->line('Name: ' . $tournament->name);
        $this->line('Status: ' . $tournament->status);
        $this->line('Match size: ' . $tournament->match_size);
        $this->line('Starts at: ' . (string) $tournament->start_at);

        return self::SUCCESS;
    }
}
