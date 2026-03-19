<?php

namespace App\Console\Commands;

use App\Models\Game;
use App\Models\Tournament;
use App\Models\User;
use App\Services\Tournament\TournamentJoinService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class PrepareTournamentLoadFixtureCommand extends Command
{
    protected $signature = 'tournaments:prepare-load-fixture
        {--match-size=4 : Players per match, supported values are 2 and 4}
        {--entries=64 : Number of total entries to create}
        {--user-ids= : Comma-separated user ids to reuse}
        {--output=storage/app/tournament-load-fixture.json : Output JSON path relative to backend root}
        {--with-tokens : Mint access tokens directly into the fixture}
        {--token-name-prefix=load-test : Prefix used when minting fixture tokens}
        {--name=Tournament Load Fixture : Tournament name}';

    protected $description = 'Create a tournament plus joined entries and export a load-test fixture JSON for socket stress tooling.';

    public function handle(TournamentJoinService $tournamentJoinService): int
    {
        $matchSize = (int) $this->option('match-size');
        $matchSize = in_array($matchSize, [2, 4], true) ? $matchSize : 4;
        $entryCount = max($matchSize, (int) $this->option('entries'));
        $name = trim((string) $this->option('name'));
        $outputPath = (string) $this->option('output');
        $withTokens = (bool) $this->option('with-tokens');
        $tokenPrefix = trim((string) $this->option('token-name-prefix')) ?: 'load-test';

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

        $userIds = collect(explode(',', (string) $this->option('user-ids')))
            ->map(fn ($id) => (int) trim($id))
            ->filter()
            ->values();

        $users = $userIds->isNotEmpty()
            ? User::query()->whereIn('id', $userIds->all())->orderBy('id')->get()
            : User::query()->orderBy('id')->limit(max(1, min($entryCount, 200)))->get();

        if ($users->isEmpty()) {
            $this->error('No users available for fixture creation.');
            return self::FAILURE;
        }

        $now = Carbon::now();
        $tournament = Tournament::query()->create([
            'uuid' => (string) Str::uuid(),
            'game_id' => $game->id,
            'slug' => 'load-fixture-' . Str::lower(Str::random(8)),
            'name' => $name,
            'code' => 'LOAD-' . strtoupper(Str::random(6)),
            'type' => 'standard',
            'status' => 'entry_open',
            'currency' => 'chips',
            'entry_fee' => 0,
            'allow_multiple_entries' => true,
            'max_entries_per_user' => $entryCount,
            'min_total_entries' => $matchSize,
            'max_total_entries' => $entryCount,
            'match_size' => $matchSize,
            'advance_count' => 1,
            'bracket_size' => null,
            'bye_count' => 0,
            'seeding_strategy' => 'random',
            'bot_fill_policy' => 'fill_after_timeout',
            'ticket_prefix' => 'LOAD',
            'next_entry_no' => 1,
            'current_total_entries' => 0,
            'current_active_entries' => 0,
            'entry_open_at' => $now->copy()->subMinute(),
            'entry_close_at' => $now->copy()->addHours(2),
            'start_at' => $now->copy()->addHours(3),
            'rules' => [
                'game_mode' => 'CLASSIC',
                'players_per_match' => $matchSize,
                'advance_count' => 1,
            ],
            'meta' => [
                'fixture_type' => 'load_test',
            ],
        ]);

        $fixtureEntries = collect();
        for ($i = 0; $i < $entryCount; $i++) {
            $user = $users[$i % $users->count()];
            $joined = $tournamentJoinService->join($tournament->fresh(), $user, 1)->first();

            $fixtureEntries->push([
                'user_id' => $user->id,
                'user_name' => $user->name ?? ('user-' . $user->id),
                'entry_uuid' => $joined->uuid ?? $joined->entry_uuid,
                'ticket_no' => $joined->ticket_no,
                'access_token' => $withTokens ? $user->createToken(sprintf('%s-user-%d-entry-%d', $tokenPrefix, $user->id, $i + 1))->plainTextToken : null,
            ]);
        }

        $absoluteOutputPath = base_path($outputPath);
        File::ensureDirectoryExists(dirname($absoluteOutputPath));
        File::put($absoluteOutputPath, json_encode([
            'tournament_uuid' => $tournament->uuid,
            'match_size' => $matchSize,
            'entries_requested' => $entryCount,
            'entries' => $fixtureEntries->all(),
            'notes' => [
                $withTokens
                    ? 'Access tokens were minted during fixture preparation.'
                    : 'Fill access_token values before running socket load script.',
            ],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        $this->info('Tournament load fixture created.');
        $this->line('Tournament UUID: ' . $tournament->uuid);
        $this->line('Entries: ' . $fixtureEntries->count());
        $this->line('Output: ' . $absoluteOutputPath);

        return self::SUCCESS;
    }
}
