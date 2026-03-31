<?php

namespace App\Services;

use App\Models\Tournament;
use App\Models\TournamentMatch;
use App\Models\TournamentMatchPlayer;
use App\Models\TournamentRegistration;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * TournamentBracketService
 *
 * Generates match brackets for:
 *  - knockout   (single elimination)
 *  - round_robin
 *  - double_elim (double elimination)
 *  - group_knockout (group stage + knockout)
 *
 * Called after registration closes.
 * Creates TournamentMatch + TournamentMatchPlayer records.
 */
class TournamentBracketService
{
    /**
     * Generate the bracket for a tournament.
     * Deletes any existing matches first (idempotent).
     */
    public function generate(Tournament $tournament): array
    {
        // Get real players only (bots added separately by admin)
        $registrations = $tournament->registrations()
            ->orderBy('id')
            ->get();

        if ($registrations->isEmpty()) {
            throw new \RuntimeException('No registrations found. Cannot generate bracket.');
        }

        // Seed players (random by default)
        $seeded = $this->seedRegistrations($registrations, $tournament);

        DB::transaction(function () use ($tournament, $seeded) {
            // Remove old bracket
            TournamentMatch::where('tournament_id', $tournament->id)->delete();

            match ($tournament->format) {
                'knockout'      => $this->generateKnockout($tournament, $seeded),
                'round_robin'   => $this->generateRoundRobin($tournament, $seeded),
                'double_elim'   => $this->generateDoubleElimination($tournament, $seeded),
                'group_knockout'=> $this->generateGroupKnockout($tournament, $seeded),
                default         => $this->generateKnockout($tournament, $seeded),
            };

            // Mark tournament as in_progress
            $tournament->update(['status' => Tournament::STATUS_IN_PROGRESS]);
        });

        return TournamentMatch::where('tournament_id', $tournament->id)
            ->with('players.registration')
            ->orderBy('round_number')
            ->orderBy('match_number')
            ->get()
            ->toArray();
    }

    // ─── Knockout (Single Elimination) ────────────────────────────────────────

    private function generateKnockout(Tournament $tournament, Collection $players): void
    {
        $perMatch   = $tournament->players_per_match; // 2 or 4
        $count      = $players->count();

        // Pad to next power of $perMatch with byes
        $bracketSize = $this->nextPowerOf($perMatch, $count);
        $byeCount    = $bracketSize - $count;

        // Pad with null (bye slots)
        $padded = $players->concat(array_fill(0, $byeCount, null));

        $round       = 1;
        $matchNumber = 1;
        $matchesThisRound = $bracketSize / $perMatch;
        $scheduledAt = $tournament->tournament_start_at ?? now()->addHour();

        // Round 1 — assign actual players
        $chunks = $padded->chunk($perMatch);
        foreach ($chunks as $chunk) {
            $match = TournamentMatch::create([
                'tournament_id' => $tournament->id,
                'round_number'  => $round,
                'match_number'  => $matchNumber,
                'status'        => TournamentMatch::STATUS_SCHEDULED,
                'scheduled_at'  => $scheduledAt,
            ]);

            $slot = 1;
            foreach ($chunk as $registration) {
                if ($registration) {
                    TournamentMatchPlayer::create([
                        'match_id'        => $match->id,
                        'registration_id' => $registration->id,
                        'slot_number'     => $slot,
                        'score'           => 0,
                    ]);

                    $registration->update(['status' => TournamentRegistration::STATUS_PLAYING]);
                }
                // Null = bye: winner auto-advances (no player entry)
                $slot++;
            }

            // Auto-advance byes (if a bye slot exists, the real player auto-wins)
            $this->autoAdvanceByes($match, $chunk, $perMatch);

            $matchNumber++;
            $scheduledAt = (clone $scheduledAt)->addHours(2);
        }

        // Create empty slots for subsequent rounds
        $round++;
        while ($matchesThisRound > 1) {
            $matchesThisRound = $matchesThisRound / $perMatch;
            $matchNumber = 1;

            for ($i = 0; $i < $matchesThisRound; $i++) {
                TournamentMatch::create([
                    'tournament_id' => $tournament->id,
                    'round_number'  => $round,
                    'match_number'  => $matchNumber,
                    'status'        => TournamentMatch::STATUS_SCHEDULED,
                    'scheduled_at'  => $scheduledAt,
                ]);
                $matchNumber++;
                $scheduledAt = (clone $scheduledAt)->addHours(2);
            }
            $round++;
        }

        // Advance bye winners: any round-1 match already completed due to a bye
        // needs its winner seeded into round-2 now that those matches exist.
        $this->advanceByeWinners($tournament);
    }

    /**
     * After all rounds are created, seed bye-match winners into round 2.
     * Called at the end of generateKnockout only.
     */
    private function advanceByeWinners(Tournament $tournament): void
    {
        $perMatch = $tournament->players_per_match;

        $byeMatches = TournamentMatch::where('tournament_id', $tournament->id)
            ->where('round_number', 1)
            ->where('status', TournamentMatch::STATUS_COMPLETED)
            ->whereNotNull('winner_registration_id')
            ->get();

        foreach ($byeMatches as $byeMatch) {
            $nextMatchNumber = (int) ceil($byeMatch->match_number / $perMatch);
            $nextRound       = 2;

            $nextMatch = TournamentMatch::where('tournament_id', $tournament->id)
                ->where('round_number', $nextRound)
                ->where('match_number', $nextMatchNumber)
                ->first();

            if (! $nextMatch) {
                continue;
            }

            $alreadyIn = TournamentMatchPlayer::where('match_id', $nextMatch->id)
                ->where('registration_id', $byeMatch->winner_registration_id)
                ->exists();

            if ($alreadyIn) {
                continue;
            }

            $nextSlot = (TournamentMatchPlayer::where('match_id', $nextMatch->id)->max('slot_number') ?? 0) + 1;
            TournamentMatchPlayer::create([
                'match_id'        => $nextMatch->id,
                'registration_id' => $byeMatch->winner_registration_id,
                'slot_number'     => $nextSlot,
                'score'           => 0,
            ]);

            // If next match is now full, mark it as waiting
            if (TournamentMatchPlayer::where('match_id', $nextMatch->id)->count() >= $perMatch) {
                $nextMatch->update(['status' => TournamentMatch::STATUS_WAITING]);
            }
        }
    }

    // ─── Round Robin ──────────────────────────────────────────────────────────

    private function generateRoundRobin(Tournament $tournament, Collection $players): void
    {
        $perMatch    = $tournament->players_per_match;
        $playerList  = $players->values()->all();
        $n           = count($playerList);
        $matchNumber = 1;
        $scheduledAt = $tournament->tournament_start_at ?? now()->addHour();

        // Generate all unique combinations of $perMatch players
        $combinations = $this->combinations($playerList, $perMatch);

        foreach ($combinations as $combo) {
            $match = TournamentMatch::create([
                'tournament_id' => $tournament->id,
                'round_number'  => 1, // Round robin is single round
                'match_number'  => $matchNumber,
                'status'        => TournamentMatch::STATUS_SCHEDULED,
                'scheduled_at'  => $scheduledAt,
            ]);

            $slot = 1;
            foreach ($combo as $registration) {
                TournamentMatchPlayer::create([
                    'match_id'        => $match->id,
                    'registration_id' => $registration->id,
                    'slot_number'     => $slot,
                    'score'           => 0,
                ]);
                $slot++;

                $registration->update(['status' => TournamentRegistration::STATUS_PLAYING]);
            }

            $matchNumber++;
            $scheduledAt = (clone $scheduledAt)->addHours(2);
        }
    }

    // ─── Double Elimination ───────────────────────────────────────────────────

    private function generateDoubleElimination(Tournament $tournament, Collection $players): void
    {
        // Winners bracket: standard knockout
        $this->generateKnockout($tournament, $players);

        // Losers bracket: create empty matches (filled as players lose from winners bracket)
        // Simplified: create the same number of matches in a "losers" stage
        $lossersRounds = (int) ceil(log(count($players), $tournament->players_per_match));
        $scheduledAt   = ($tournament->tournament_start_at ?? now()->addHour())->addDays(1);
        $matchNumber   = 1;

        for ($r = 1; $r <= $lossersRounds; $r++) {
            $matchesInRound = (int) ceil(count($players) / (2 * pow($tournament->players_per_match, $r)));
            for ($m = 0; $m < max(1, $matchesInRound); $m++) {
                TournamentMatch::create([
                    'tournament_id' => $tournament->id,
                    'round_number'  => 100 + $r, // Losers bracket rounds start at 100
                    'match_number'  => $matchNumber,
                    'status'        => TournamentMatch::STATUS_SCHEDULED,
                    'scheduled_at'  => $scheduledAt,
                ]);
                $matchNumber++;
                $scheduledAt = (clone $scheduledAt)->addHours(2);
            }
        }

        // Grand Final match
        TournamentMatch::create([
            'tournament_id' => $tournament->id,
            'round_number'  => 200, // Grand Final
            'match_number'  => 1,
            'status'        => TournamentMatch::STATUS_SCHEDULED,
            'scheduled_at'  => $scheduledAt->addDays(1),
        ]);
    }

    // ─── Group Stage + Knockout ───────────────────────────────────────────────

    private function generateGroupKnockout(Tournament $tournament, Collection $players): void
    {
        $perMatch     = $tournament->players_per_match;
        $groupSize    = max($perMatch * 2, 4); // Each group has 4-8 players
        $groups       = $players->chunk($groupSize);
        $scheduledAt  = $tournament->tournament_start_at ?? now()->addHour();
        $matchNumber  = 1;

        // Group stage round-robin within each group
        foreach ($groups as $groupIndex => $group) {
            $combos = $this->combinations($group->values()->toArray(), $perMatch);
            foreach ($combos as $combo) {
                $match = TournamentMatch::create([
                    'tournament_id' => $tournament->id,
                    'round_number'  => 1, // Group stage = round 1
                    'match_number'  => $matchNumber,
                    'status'        => TournamentMatch::STATUS_SCHEDULED,
                    'scheduled_at'  => $scheduledAt,
                ]);

                $slot = 1;
                foreach ($combo as $registration) {
                    TournamentMatchPlayer::create([
                        'match_id'        => $match->id,
                        'registration_id' => $registration->id,
                        'slot_number'     => $slot,
                        'score'           => 0,
                    ]);
                    $slot++;
                    $registration->update(['status' => TournamentRegistration::STATUS_PLAYING]);
                }

                $matchNumber++;
                $scheduledAt = (clone $scheduledAt)->addHours(2);
            }
        }

        // Knockout stage — empty matches for top 2 from each group
        $knockoutPlayers = $groups->count() * 2; // top 2 per group
        $knockoutRounds  = (int) ceil(log($knockoutPlayers, $perMatch));

        for ($r = 1; $r <= $knockoutRounds; $r++) {
            $matchesInRound = (int) ceil($knockoutPlayers / (pow($perMatch, $r)));
            for ($m = 1; $m <= max(1, $matchesInRound); $m++) {
                TournamentMatch::create([
                    'tournament_id' => $tournament->id,
                    'round_number'  => 10 + $r, // Knockout rounds start at 11
                    'match_number'  => $m,
                    'status'        => TournamentMatch::STATUS_SCHEDULED,
                    'scheduled_at'  => $scheduledAt,
                ]);
                $scheduledAt = (clone $scheduledAt)->addHours(2);
            }
        }
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function seedRegistrations(Collection $registrations, Tournament $tournament): Collection
    {
        // Assign seed numbers (random shuffle)
        $shuffled = $registrations->shuffle();
        $seed     = 1;

        foreach ($shuffled as $reg) {
            $reg->update(['seed_number' => $seed++]);
        }

        return $shuffled->values();
    }

    private function autoAdvanceByes(TournamentMatch $match, $chunk, int $perMatch): void
    {
        $realPlayers = collect($chunk)->filter()->count();

        if ($realPlayers === $perMatch) {
            return; // No byes — normal match
        }

        if ($realPlayers === 0) {
            $match->update(['status' => TournamentMatch::STATUS_CANCELLED]);
            return;
        }

        // One real player vs bye → auto-win for the real player
        $realPlayer = collect($chunk)->filter()->first();
        if ($realPlayer && $realPlayers === 1) {
            TournamentMatchPlayer::where('match_id', $match->id)
                ->where('registration_id', $realPlayer->id)
                ->update(['result' => 'win', 'finish_position' => 1]);

            $match->update([
                'status'                 => TournamentMatch::STATUS_COMPLETED,
                'winner_registration_id' => $realPlayer->id,
                'started_at'             => now(),
                'ended_at'               => now(),
            ]);
        }
    }

    private function nextPowerOf(int $base, int $n): int
    {
        $power = 1;
        while ($power < $n) {
            $power *= $base;
        }
        return $power;
    }

    /**
     * Generate all C(n, k) combinations from an array.
     */
    private function combinations(array $array, int $size): array
    {
        if ($size === 0) {
            return [[]];
        }

        if (empty($array)) {
            return [];
        }

        $first = array_shift($array);
        $withFirst = array_map(
            fn ($combo) => array_merge([$first], $combo),
            $this->combinations($array, $size - 1)
        );
        $withoutFirst = $this->combinations($array, $size);

        return array_merge($withFirst, $withoutFirst);
    }
}
