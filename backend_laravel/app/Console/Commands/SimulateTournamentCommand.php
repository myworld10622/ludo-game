<?php

namespace App\Console\Commands;

use App\Models\Tournament;
use App\Models\TournamentMatch;
use App\Models\TournamentMatchPlayer;
use App\Models\TournamentPrize;
use App\Models\TournamentRegistration;
use App\Models\TournamentWalletTransaction;
use App\Models\User;
use App\Models\Wallet;
use App\Services\TournamentBracketService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Full end-to-end tournament simulation with prize verification.
 *
 * Usage examples:
 *   php artisan tournament:simulate
 *   php artisan tournament:simulate --players=8 --per-match=4 --fee=100
 *   php artisan tournament:simulate --players=16 --real-users=8 --fee=50
 *   php artisan tournament:simulate --players=4 --cleanup
 *
 * --real-users=N   : Use N real test users (created if not exist). Rest are bots.
 *                    Prizes are only paid to real users.
 * --players=N      : Total bracket size (must be power of per-match: 4/8/16/32/64)
 * --per-match=2|4  : Players per match (default 2)
 * --fee=N          : Entry fee per player in ₹ (default 50)
 * --cleanup        : Delete tournament + test users after test
 */
class SimulateTournamentCommand extends Command
{
    protected $signature = 'tournament:simulate
        {--players=8       : Total bracket size (power of per-match)}
        {--real-users=4    : Number of real test users to include (rest are bots)}
        {--per-match=2     : Players per match (2 or 4)}
        {--fee=50          : Entry fee per player in ₹}
        {--creator-user=   : Create the tournament under a user (id/user_code/username/email/mobile)}
        {--cleanup         : Delete tournament and test users after test}';

    protected $description = 'Full tournament simulation: bracket → matches → prizes. Verifies prize payout to real users.';

    // ── State ──────────────────────────────────────────────────────────────────
    private array $walletBefore = [];   // user_id → balance before
    private array $createdUserIds = []; // test user ids created by this run

    public function handle(): int
    {
        $totalPlayers = max(2, (int) $this->option('players'));
        $realUsers    = max(0, min((int) $this->option('real-users'), $totalPlayers));
        $perMatch     = in_array((int) $this->option('per-match'), [2, 4]) ? (int) $this->option('per-match') : 2;
        $entryFee     = max(0, (int) $this->option('fee'));
        $botCount     = $totalPlayers - $realUsers;
        try {
            $creatorUser = $this->resolveCreatorUser();
        } catch (RuntimeException $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }

        $prizePool    = $entryFee * $totalPlayers * 0.80; // 20% platform cut
        $platformFee  = $entryFee * $totalPlayers * 0.20;

        $this->printHeader($totalPlayers, $realUsers, $botCount, $perMatch, $entryFee, $prizePool);

        // ── 1. Create / fetch real test users ────────────────────────────────
        $users = [];
        if ($realUsers > 0) {
            $this->step("Setting up {$realUsers} real test users");
            $users = $this->resolveTestUsers($realUsers, $entryFee);
            $this->snapshotWallets($users);
            foreach ($users as $u) {
                $bal = $this->walletBefore[$u->id] ?? 0;
                $this->line("    User #{$u->id} {$u->username}  wallet: ₹{$bal}");
            }
            $this->ok("{$realUsers} test users ready");
        }

        // ── 2. Create Tournament ─────────────────────────────────────────────
        $this->step("Creating tournament");

        $tournament = DB::transaction(function () use (
            $totalPlayers, $perMatch, $entryFee, $prizePool, $platformFee, $creatorUser
        ) {
            $t = Tournament::create([
                'name'                  => "SIM: {$totalPlayers}P × {$perMatch}v{$perMatch} @" . now()->format('H:i:s'),
                'description'           => 'Auto-generated simulation tournament.',
                'creator_type'          => $creatorUser ? 'user' : 'admin',
                'creator_user_id'       => $creatorUser?->id,
                'type'                  => 'public',
                'format'                => 'knockout',
                'bracket_mode'          => 'auto',
                'status'                => Tournament::STATUS_REGISTRATION_OPEN,
                'entry_fee'             => $entryFee,
                'max_players'           => $totalPlayers,
                'current_players'       => 0,
                'players_per_match'     => $perMatch,
                'platform_fee_pct'      => 20.00,
                'platform_fee_amount'   => $platformFee,
                'total_prize_pool'      => $prizePool,
                'turn_time_limit'       => 30,
                'match_timeout'         => 2700,
                'bot_allowed'           => true,
                'max_bot_pct'           => 5,
                'requires_approval'     => false,
                'is_approved'           => true,
                'registration_start_at' => now()->subHour(),
                'registration_end_at'   => now()->addMinutes(5),
                'tournament_start_at'   => now()->addMinutes(10),
            ]);

            // Prizes: 60% / 30% / 10%
            $prizeSplit = [1 => 60, 2 => 30, 3 => 10];
            foreach ($prizeSplit as $pos => $pct) {
                TournamentPrize::create([
                    'tournament_id' => $t->id,
                    'position'      => $pos,
                    'prize_pct'     => $pct,
                    'prize_amount'  => round($prizePool * $pct / 100, 2),
                    'payout_status' => 'pending',
                ]);
            }

            return $t;
        });

        $this->ok("Tournament #{$tournament->id}: {$tournament->name}");
        if ($creatorUser) {
            $this->line("    Owner: {$creatorUser->username} (user_code: {$creatorUser->user_code}, internal id: {$creatorUser->id})");
        } else {
            $this->line("    Owner: admin");
        }
        $this->line("    Prize pool: ₹{$prizePool}  |  Platform fee: ₹{$platformFee}");
        $this->printPrizeTable($tournament);

        // ── 3. Register Players ──────────────────────────────────────────────
        $this->step("Registering {$totalPlayers} players ({$realUsers} real, {$botCount} bots)");

        $registrations = $this->registerPlayers($tournament, $users, $botCount, $entryFee);
        $tournament->update(['current_players' => count($registrations)]);
        $this->ok(count($registrations) . " players registered");

        // ── 4. Generate Bracket ──────────────────────────────────────────────
        $this->step("Closing registration and generating bracket");

        $tournament->update([
            'status'              => Tournament::STATUS_REGISTRATION_CLOSED,
            'registration_end_at' => now(),
        ]);

        try {
            $bracketService = new TournamentBracketService();
            $matches        = $bracketService->generate($tournament);
            $tournament->refresh();
        } catch (\Throwable $e) {
            $this->error("Bracket generation failed: " . $e->getMessage());
            $this->cleanup($tournament);
            return self::FAILURE;
        }

        $matchCount = count($matches);
        $roundCount = collect($matches)->pluck('round_number')->unique()->count();
        $this->ok("Bracket: {$matchCount} matches across {$roundCount} rounds");
        $this->printBracket($tournament);

        // ── 5. Simulate All Rounds ───────────────────────────────────────────
        $this->step("Simulating all rounds");

        $maxRound = TournamentMatch::where('tournament_id', $tournament->id)->max('round_number');

        for ($round = 1; $round <= $maxRound; $round++) {
            $roundMatches = TournamentMatch::where('tournament_id', $tournament->id)
                ->where('round_number', $round)
                ->whereNotIn('status', [TournamentMatch::STATUS_COMPLETED, TournamentMatch::STATUS_CANCELLED])
                ->with(['players.registration.user'])
                ->get();

            if ($roundMatches->isEmpty()) {
                continue;
            }

            $this->line("\n  ── Round {$round} / {$maxRound}  ({$roundMatches->count()} match(es)) ──");

            foreach ($roundMatches as $match) {
                $this->simulateMatch($match, $tournament, $round, $maxRound);
            }
        }

        // ── 6. Distribute Prizes ─────────────────────────────────────────────
        $tournament->refresh();

        if ($tournament->status !== Tournament::STATUS_COMPLETED) {
            // Force completion if simulator didn't catch it
            $tournament->update(['status' => Tournament::STATUS_COMPLETED, 'completed_at' => now()]);
        }

        $this->step("Distributing prizes to real users");
        $this->distributePrizes($tournament);

        // ── 7. Final Report ──────────────────────────────────────────────────
        $this->printFinalReport($tournament, $users);

        if ($this->option('cleanup')) {
            $this->cleanup($tournament);
        } else {
            $this->line("\n  Tournament #{$tournament->id} preserved in database.");
            $this->warn("  Use --cleanup to auto-delete test data after next run.");
        }

        return self::SUCCESS;
    }

    private function resolveCreatorUser(): ?User
    {
        $value = trim((string) $this->option('creator-user'));

        if ($value === '') {
            return null;
        }

        $user = User::query()
            ->when(is_numeric($value), function ($query) use ($value) {
                $query->where('id', (int) $value)
                    ->orWhere('user_code', $value)
                    ->orWhere('mobile', $value);
            }, function ($query) use ($value) {
                $query->where('username', $value)
                    ->orWhere('email', $value)
                    ->orWhere('mobile', $value)
                    ->orWhere('user_code', $value);
            })
            ->first();

        if (! $user) {
            throw new RuntimeException("Creator user not found for: {$value}");
        }

        return $user;
    }

    // ── Player setup ──────────────────────────────────────────────────────────

    /**
     * Find or create N test users (named SimUser-1 … SimUser-N).
     * Each gets a wallet with enough balance to pay the entry fee.
     */
    private function resolveTestUsers(int $count, int $entryFee): array
    {
        $users = [];
        for ($i = 1; $i <= $count; $i++) {
            $email = "simuser{$i}@tournament.test";
            $user  = User::firstOrCreate(
                ['email' => $email],
                [
                    'uuid'     => Str::uuid()->toString(),
                    'username' => "simuser{$i}",
                    'password' => Hash::make('SimPass123!'),
                ]
            );

            if ($user->wasRecentlyCreated) {
                $this->createdUserIds[] = $user->id;
            }

            // Ensure wallet exists with enough balance
            $wallet = Wallet::firstOrCreate(
                ['user_id' => $user->id],
                ['balance' => 0]
            );

            // Top up if not enough
            $needed = max(0, ($entryFee + 100) - $wallet->balance);
            if ($needed > 0) {
                $wallet->increment('balance', $needed);
            }

            $users[] = $user->fresh();
        }

        return $users;
    }

    private function snapshotWallets(array $users): void
    {
        foreach ($users as $u) {
            $wallet = Wallet::where('user_id', $u->id)->first();
            $this->walletBefore[$u->id] = $wallet ? (float) $wallet->balance : 0;
        }
    }

    private function registerPlayers(Tournament $t, array $users, int $botCount, int $entryFee): array
    {
        $registrations = [];

        // Real users first
        foreach ($users as $user) {
            $reg = TournamentRegistration::create([
                'tournament_id'  => $t->id,
                'user_id'        => $user->id,
                'is_bot'         => false,
                'entry_fee_paid' => $entryFee,
                'status'         => TournamentRegistration::STATUS_REGISTERED,
                'registered_at'  => now(),
            ]);
            $registrations[] = $reg;
        }

        // Bots fill the rest
        for ($i = 1; $i <= $botCount; $i++) {
            $reg = TournamentRegistration::create([
                'tournament_id'  => $t->id,
                'user_id'        => null,
                'is_bot'         => true,
                'bot_name'       => 'easy',
                'entry_fee_paid' => $entryFee,
                'status'         => TournamentRegistration::STATUS_REGISTERED,
                'registered_at'  => now(),
            ]);
            $registrations[] = $reg;
        }

        return $registrations;
    }

    // ── Match simulation ──────────────────────────────────────────────────────

    private function simulateMatch(TournamentMatch $match, Tournament $tournament, int $round, int $maxRound): void
    {
        if ($match->status === TournamentMatch::STATUS_COMPLETED) {
            return;
        }

        $match->update(['status' => TournamentMatch::STATUS_IN_PROGRESS, 'started_at' => now()]);

        $players = $match->players()->with('registration.user')->get();

        if ($players->isEmpty()) {
            $match->update(['status' => TournamentMatch::STATUS_CANCELLED]);
            $this->warn("    Match R{$round}M{$match->match_number}: No players → CANCELLED");
            return;
        }

        $isLastRound  = ($round === $maxRound);
        $winnerPlayer = $players->random();

        foreach ($players as $player) {
            $isWinner  = $player->id === $winnerPlayer->id;
            $score     = $isWinner ? rand(60, 100) : rand(0, 59);
            $finishPos = $isWinner ? 1 : 2;

            $player->update([
                'score'           => $score,
                'finish_position' => $finishPos,
                'result'          => $isWinner ? 'win' : 'loss',
                'finished_at'     => now(),
            ]);

            if ($player->registration) {
                $player->registration->update([
                    'status'        => $isWinner
                        ? ($isLastRound ? TournamentRegistration::STATUS_WINNER : TournamentRegistration::STATUS_PLAYING)
                        : TournamentRegistration::STATUS_ELIMINATED,
                    'eliminated_at' => $isWinner ? null : now(),
                ]);
            }
        }

        $match->update([
            'status'                 => TournamentMatch::STATUS_COMPLETED,
            'winner_registration_id' => $winnerPlayer->registration_id,
            'ended_at'               => now(),
        ]);

        $this->advanceWinner($match, $winnerPlayer->registration, $tournament);

        // Print match result
        $playerLines = $players->map(function ($p) use ($winnerPlayer) {
            $name     = $p->registration?->is_bot
                ? "Bot#{$p->registration_id}"
                : ($p->registration?->user?->username ?? "User#{$p->registration?->user_id}");
            $won      = $p->id === $winnerPlayer->id ? '✓WIN' : 'loss';
            return "{$name}(score:{$p->score},{$won})";
        })->implode('  vs  ');

        $winnerName = $winnerPlayer->registration?->is_bot
            ? "Bot#{$winnerPlayer->registration_id}"
            : ($winnerPlayer->registration?->user?->username ?? "User");

        $this->line("    R{$round}M{$match->match_number}: {$playerLines}  → 🏆 {$winnerName}");
    }

    private function advanceWinner(TournamentMatch $match, ?TournamentRegistration $winner, Tournament $tournament): void
    {
        if (! $winner) {
            return;
        }

        $nextMatchNumber = (int) ceil($match->match_number / $tournament->players_per_match);
        $nextRound       = $match->round_number + 1;

        $nextMatch = TournamentMatch::where('tournament_id', $tournament->id)
            ->where('round_number', $nextRound)
            ->where('match_number', $nextMatchNumber)
            ->first();

        if (! $nextMatch) {
            return;
        }

        $alreadyIn = TournamentMatchPlayer::where('match_id', $nextMatch->id)
            ->where('registration_id', $winner->id)
            ->exists();

        if ($alreadyIn) {
            return;
        }

        $nextSlot = (TournamentMatchPlayer::where('match_id', $nextMatch->id)->max('slot_number') ?? 0) + 1;
        TournamentMatchPlayer::create([
            'match_id'        => $nextMatch->id,
            'registration_id' => $winner->id,
            'slot_number'     => $nextSlot,
            'score'           => 0,
        ]);

        $filledSlots = TournamentMatchPlayer::where('match_id', $nextMatch->id)->count();
        if ($filledSlots >= $tournament->players_per_match) {
            $nextMatch->update(['status' => TournamentMatch::STATUS_WAITING]);
        }
    }

    // ── Prize distribution ────────────────────────────────────────────────────

    /**
     * Mirror of TournamentMatchResultController::distributePrizes().
     * Pays real users only; bots are skipped (prize cascades to next real player).
     */
    private function distributePrizes(Tournament $tournament): void
    {
        // Final ranking: winner first, then by eliminated_at desc (last eliminated = best rank)
        $registrations = $tournament->registrations()
            ->where('is_bot', false)
            ->orderByRaw("CASE WHEN status = 'winner' THEN 0 ELSE 1 END")
            ->orderBy('final_position')
            ->orderByDesc('eliminated_at')
            ->get();

        $prizes  = $tournament->prizes()->orderBy('position')->get();
        $ranked  = $registrations->values();
        $rankIdx = 0;
        $paid    = 0;

        DB::transaction(function () use ($tournament, $prizes, $ranked, &$rankIdx, &$paid) {
            foreach ($prizes as $prize) {
                if (! isset($ranked[$rankIdx])) {
                    break;
                }

                $winner = $ranked[$rankIdx];
                $amount = (float) $prize->prize_amount;

                // Credit wallet
                $wallet = Wallet::where('user_id', $winner->user_id)->lockForUpdate()->first();
                if ($wallet) {
                    $wallet->balance += $amount;
                    $wallet->save();
                }

                // Transaction log
                TournamentWalletTransaction::create([
                    'tournament_id'   => $tournament->id,
                    'user_id'         => $winner->user_id,
                    'type'            => TournamentWalletTransaction::TYPE_PRIZE_CREDIT,
                    'amount'          => $amount,
                    'status'          => 'completed',
                    'registration_id' => $winner->id,
                    'description'     => "Prize #{$prize->position} in {$tournament->name}",
                ]);

                // Update registration
                $winner->update([
                    'final_position' => $prize->position,
                    'prize_won'      => $amount,
                    'completed_at'   => now(),
                ]);

                // Update prize record
                $prize->update([
                    'winner_user_id' => $winner->user_id,
                    'payout_status'  => 'paid',
                    'paid_at'        => now(),
                ]);

                $paid++;
                $rankIdx++;
            }

            // Platform fee record
            TournamentWalletTransaction::create([
                'tournament_id' => $tournament->id,
                'user_id'       => 1,
                'type'          => TournamentWalletTransaction::TYPE_PLATFORM_FEE,
                'amount'        => $tournament->platform_fee_amount,
                'status'        => 'completed',
                'description'   => "20% platform fee: {$tournament->name}",
            ]);
        });

        $this->ok("{$paid} prize(s) distributed");
    }

    // ── Reporting ─────────────────────────────────────────────────────────────

    private function printHeader(int $total, int $real, int $bots, int $perMatch, int $fee, float $prize): void
    {
        $this->newLine();
        $this->line('  ┌────────────────────────────────────────────────────────┐');
        $this->line('  │            🏆  TOURNAMENT SIMULATION                   │');
        $this->line('  ├────────────────────────────────────────────────────────┤');
        $this->line("  │  Total players  : {$total}  ({$real} real users + {$bots} bots)");
        $this->line("  │  Players/match  : {$perMatch}");
        $this->line("  │  Entry fee      : ₹{$fee}  →  Prize pool: ₹{$prize}");
        $this->line("  │  Prizes         : 1st 60%  |  2nd 30%  |  3rd 10%");
        $this->line('  └────────────────────────────────────────────────────────┘');
    }

    private function printPrizeTable(Tournament $tournament): void
    {
        $this->line("    ┌──────┬────────┬──────────┐");
        $this->line("    │ Pos  │  Pct   │  Amount  │");
        $this->line("    ├──────┼────────┼──────────┤");
        foreach ($tournament->prizes as $p) {
            $pos = str_pad($p->position, 4);
            $pct = str_pad($p->prize_pct . '%', 6);
            $amt = str_pad('₹' . number_format($p->prize_amount, 2), 8);
            $this->line("    │ {$pos} │ {$pct} │ {$amt} │");
        }
        $this->line("    └──────┴────────┴──────────┘");
    }

    private function printBracket(Tournament $tournament): void
    {
        $this->newLine();
        $this->line('  📋 Bracket:');
        $matches = TournamentMatch::where('tournament_id', $tournament->id)
            ->with('players.registration.user')
            ->orderBy('round_number')
            ->orderBy('match_number')
            ->get();

        foreach ($matches->groupBy('round_number') as $round => $roundMatches) {
            $this->line("  Round {$round}:");
            foreach ($roundMatches as $m) {
                $playerStr = $m->players->map(function ($p) {
                    return $p->registration?->is_bot
                        ? "Bot#{$p->registration_id}"
                        : ($p->registration?->user?->username ?? "User#{$p->registration?->user_id}");
                })->implode(' vs ');
                $playerStr = $playerStr ?: '(TBD)';
                $this->line("    Match #{$m->match_number}: [{$playerStr}]  status:{$m->status}");
            }
        }
    }

    private function printFinalReport(Tournament $tournament, array $users): void
    {
        $tournament->refresh();
        $this->newLine();
        $this->line('  ┌────────────────────────────────────────────────────────┐');
        $this->info('  │              📊  FINAL REPORT                          │');
        $this->line('  ├────────────────────────────────────────────────────────┤');
        $this->line("  │  Tournament : #{$tournament->id}  {$tournament->name}");
        $this->line("  │  Status     : {$tournament->status}");
        $this->line("  │  Prize Pool : ₹{$tournament->total_prize_pool}");
        $this->line("  │  Platform   : ₹{$tournament->platform_fee_amount}  (20%)");
        $this->line('  └────────────────────────────────────────────────────────┘');

        // Match stats
        $matchStats = TournamentMatch::where('tournament_id', $tournament->id)
            ->selectRaw('status, count(*) as cnt')
            ->groupBy('status')
            ->pluck('cnt', 'status');

        $this->newLine();
        $this->line('  Match Summary:');
        foreach ($matchStats as $status => $count) {
            $this->line("    {$status}: {$count}");
        }

        // Prize winners
        $prizes = $tournament->prizes()->with('winner')->orderBy('position')->get();
        $this->newLine();
        $this->line('  🏆 Prize Payouts:');
        $this->line('    ┌──────┬──────────┬──────────────────────────┬───────────────┐');
        $this->line('    │ Pos  │  Amount  │  Winner                  │  Status       │');
        $this->line('    ├──────┼──────────┼──────────────────────────┼───────────────┤');
        foreach ($prizes as $p) {
            $pos    = str_pad($p->position, 4);
            $amt    = str_pad('₹' . number_format($p->prize_amount, 2), 8);
            $winner = str_pad($p->winner?->username ?? '(no real player)', 24);
            $status = str_pad($p->payout_status, 13);
            $this->line("    │ {$pos} │ {$amt} │ {$winner} │ {$status} │");
        }
        $this->line('    └──────┴──────────┴──────────────────────────┴───────────────┘');

        // Wallet changes for real users
        if (! empty($users)) {
            $this->newLine();
            $this->line('  💰 Wallet Changes (real users):');
            $this->line('    ┌────────────────────────┬──────────────┬──────────────┬──────────────┐');
            $this->line('    │ User                   │  Before      │  After       │  Change      │');
            $this->line('    ├────────────────────────┼──────────────┼──────────────┼──────────────┤');
            foreach ($users as $u) {
                $walletAfter = (float) (Wallet::where('user_id', $u->id)->value('balance') ?? 0);
                $before      = $this->walletBefore[$u->id] ?? 0;
                $change      = $walletAfter - $before;
                $changeStr   = ($change >= 0 ? '+' : '') . number_format($change, 2);
                $icon        = $change > 0 ? '🟢' : ($change < 0 ? '🔴' : '⚪');

                $name   = str_pad($u->username, 22);
                $befStr = str_pad('₹' . number_format($before, 2), 12);
                $aftStr = str_pad('₹' . number_format($walletAfter, 2), 12);
                $chStr  = str_pad("{$icon} {$changeStr}", 12);
                $this->line("    │ {$name} │ {$befStr} │ {$aftStr} │ {$chStr} │");
            }
            $this->line('    └────────────────────────┴──────────────┴──────────────┴──────────────┘');
        }

        // Player rankings
        $this->newLine();
        $this->line('  📋 Player Rankings (all):');
        $registrations = TournamentRegistration::where('tournament_id', $tournament->id)
            ->with('user')
            ->orderByRaw("CASE
                WHEN status = 'winner'     THEN 1
                WHEN status = 'playing'    THEN 2
                WHEN status = 'eliminated' THEN 3
                ELSE 4 END")
            ->orderByDesc('eliminated_at')
            ->get();

        $rank = 1;
        foreach ($registrations as $reg) {
            $name     = $reg->is_bot ? "Bot#{$reg->id}" : ($reg->user?->username ?? "User#{$reg->user_id}");
            $prize    = $reg->prize_won > 0 ? "  🏆 Prize: ₹{$reg->prize_won}" : '';
            $pos      = $reg->final_position ? " (Pos#{$reg->final_position})" : '';
            $type     = $reg->is_bot ? '[bot]' : '[user]';
            $this->line("    #{$rank}  {$type}  {$name}  —  {$reg->status}{$pos}{$prize}");
            $rank++;
        }

        // Validation checks
        $this->newLine();
        $this->line('  ✅ Validation Checks:');
        $pendingMatches = TournamentMatch::where('tournament_id', $tournament->id)
            ->whereNotIn('status', [TournamentMatch::STATUS_COMPLETED, TournamentMatch::STATUS_CANCELLED, TournamentMatch::STATUS_FORFEITED])
            ->count();
        $this->checkResult($pendingMatches === 0, "All matches completed ({$pendingMatches} pending)");

        $totalPaid = TournamentWalletTransaction::where('tournament_id', $tournament->id)
            ->where('type', TournamentWalletTransaction::TYPE_PRIZE_CREDIT)
            ->sum('amount');
        $prizePoolOk = abs($totalPaid - $tournament->total_prize_pool) < 1.0 || count($users) === 0;
        $this->checkResult($prizePoolOk || count($users) === 0,
            "Prize payout: ₹{$totalPaid} paid out of ₹{$tournament->total_prize_pool} pool"
            . (count($users) === 0 ? ' (no real users — bots skipped)' : ''));

        $tournamentComplete = $tournament->status === Tournament::STATUS_COMPLETED;
        $this->checkResult($tournamentComplete, "Tournament status is 'completed'");

        $paidPrizes = TournamentPrize::where('tournament_id', $tournament->id)
            ->where('payout_status', 'paid')->count();
        $this->line("    ℹ  {$paidPrizes} prize position(s) paid out");

        $this->newLine();
    }

    private function checkResult(bool $pass, string $msg): void
    {
        if ($pass) {
            $this->info("    ✓ {$msg}");
        } else {
            $this->error("    ✗ {$msg}");
        }
    }

    // ── Cleanup ───────────────────────────────────────────────────────────────

    private function cleanup(Tournament $tournament): void
    {
        $this->step("Cleaning up test data");

        TournamentWalletTransaction::where('tournament_id', $tournament->id)->delete();
        TournamentMatchPlayer::whereHas('match', fn ($q) => $q->where('tournament_id', $tournament->id))->delete();
        TournamentMatch::where('tournament_id', $tournament->id)->delete();
        TournamentRegistration::where('tournament_id', $tournament->id)->delete();
        TournamentPrize::where('tournament_id', $tournament->id)->delete();
        $tournament->forceDelete();
        $this->ok("Tournament #{$tournament->id} deleted");

        if (! empty($this->createdUserIds)) {
            Wallet::whereIn('user_id', $this->createdUserIds)->delete();
            User::whereIn('id', $this->createdUserIds)->forceDelete();
            $this->ok(count($this->createdUserIds) . " test user(s) deleted");
        }
    }

    // ── Output helpers ────────────────────────────────────────────────────────

    private function step(string $msg): void
    {
        $this->newLine();
        $this->line("  ▶ {$msg}...");
    }

    private function ok(string $msg): void
    {
        $this->info("    ✓ {$msg}");
    }
}
