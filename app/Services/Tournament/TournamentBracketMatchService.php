<?php

namespace App\Services\Tournament;

use App\Models\Tournament;
use App\Models\TournamentEntry;
use App\Models\TournamentMatch;
use App\Models\TournamentMatchEntry;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class TournamentBracketMatchService
{
    public function __construct(
        private readonly TournamentBracketPlannerService $tournamentBracketPlannerService
    ) {
    }

    public function seedRound(Tournament $tournament, Collection $entries, int $roundNo, int $tableSize, int $advanceCount): Collection
    {
        $plans = $this->tournamentBracketPlannerService->planRound($tournament, $entries, $roundNo, $tableSize);
        $matchRows = [];
        $now = now();

        foreach ($plans as $plan) {
            $matchRows[] = [
                'match_uuid' => (string) Str::uuid(),
                'tournament_id' => $tournament->id,
                'game_id' => $tournament->game_id,
                'round_no' => (int) $plan['match_no'] >= 0 ? $roundNo : $roundNo,
                'match_no' => (int) $plan['match_no'],
                'bracket_position' => (int) $plan['bracket_position'],
                'stage' => 'main',
                'status' => ((int) $plan['assigned_count'] === 1) ? 'completed' : 'pending',
                'winner_entry_id' => ((int) $plan['assigned_count'] === 1) ? data_get($plan, 'entries.0.id') : null,
                'max_players' => $tableSize,
                'table_fee' => $tournament->entry_fee,
                'node_room_id' => null,
                'external_match_ref' => null,
                'scheduled_at' => null,
                'started_at' => null,
                'completed_at' => ((int) $plan['assigned_count'] === 1) ? $now : null,
                'settings' => json_encode([
                    'match_size' => $tableSize,
                    'advance_count' => $advanceCount,
                ], JSON_THROW_ON_ERROR),
                'meta' => json_encode([
                    'assigned_entry_count' => (int) $plan['assigned_count'],
                    'bye_slots' => (int) $plan['bye_slots'],
                ], JSON_THROW_ON_ERROR),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        TournamentMatch::query()->upsert(
            $matchRows,
            ['tournament_id', 'round_no', 'match_no', 'stage'],
            [
                'game_id',
                'bracket_position',
                'status',
                'winner_entry_id',
                'max_players',
                'table_fee',
                'node_room_id',
                'external_match_ref',
                'scheduled_at',
                'started_at',
                'completed_at',
                'settings',
                'meta',
                'updated_at',
            ]
        );

        $matches = TournamentMatch::query()
            ->where('tournament_id', $tournament->id)
            ->where('round_no', $roundNo)
            ->where('stage', 'main')
            ->orderBy('match_no')
            ->get()
            ->keyBy('match_no');

        $matchEntryRows = [];

        foreach ($plans as $plan) {
            $match = $matches->get((int) $plan['match_no']);
            if (! $match) {
                continue;
            }

            foreach ($plan['entries']->values() as $seatIndex => $entry) {
                $isByeWinner = ((int) $plan['assigned_count'] === 1);
                $matchEntryRows[] = [
                    'tournament_match_id' => $match->id,
                    'tournament_entry_id' => $entry->id,
                    'user_id' => $entry->user_id,
                    'seat_no' => $seatIndex + 1,
                    'position' => $isByeWinner ? 1 : null,
                    'score' => 0,
                    'is_winner' => $isByeWinner,
                    'status' => $isByeWinner ? 'completed' : 'seeded',
                    'stats' => json_encode([
                        'ticket_no' => $entry->ticket_no,
                        'entry_no' => $entry->entry_no,
                    ], JSON_THROW_ON_ERROR),
                    'joined_at' => null,
                    'finished_at' => $isByeWinner ? $now : null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        if (! empty($matchEntryRows)) {
            TournamentMatchEntry::query()->upsert(
                $matchEntryRows,
                ['tournament_match_id', 'tournament_entry_id'],
                [
                    'user_id',
                    'seat_no',
                    'position',
                    'score',
                    'is_winner',
                    'status',
                    'stats',
                    'joined_at',
                    'finished_at',
                    'updated_at',
                ]
            );
        }

        return TournamentMatch::query()
            ->with('entries')
            ->where('tournament_id', $tournament->id)
            ->where('round_no', $roundNo)
            ->where('stage', 'main')
            ->orderBy('match_no')
            ->get();
    }

    public function markProvisioned(TournamentMatch $match, string $roomUuid, ?int $roomId = null): void
    {
        $match->status = 'assigned';
        $match->external_match_ref = $roomUuid;
        $match->node_room_id = $roomId ? (string) $roomId : $match->node_room_id;
        $match->scheduled_at = $match->scheduled_at ?? now();
        $match->save();
    }

    public function completeMatch(TournamentMatch $match, array $rankings): void
    {
        $winnerEntryId = null;

        foreach ($rankings as $ranking) {
            $entryId = (int) $ranking['tournament_entry_id'];
            $finalRank = (int) $ranking['final_rank'];
            $score = (float) ($ranking['score'] ?? 0);

            TournamentMatchEntry::query()
                ->where('tournament_match_id', $match->id)
                ->where('tournament_entry_id', $entryId)
                ->update([
                    'position' => $finalRank,
                    'score' => $score,
                    'is_winner' => $finalRank === 1,
                    'status' => 'completed',
                    'finished_at' => now(),
                ]);

            if ($finalRank === 1) {
                $winnerEntryId = $entryId;
            }
        }

        $match->status = 'completed';
        $match->winner_entry_id = $winnerEntryId;
        $match->completed_at = now();
        $match->save();
    }

    public function resolveCurrentRoundForEntryIds(Tournament $tournament, Collection $entryIds): int
    {
        return (int) TournamentMatch::query()
            ->where('tournament_id', $tournament->id)
            ->whereHas('entries', function ($query) use ($entryIds) {
                $query->whereIn('tournament_entry_id', $entryIds->all());
            })
            ->max('round_no');
    }

    public function hasPendingMatchesInRound(Tournament $tournament, int $roundNo): bool
    {
        return TournamentMatch::query()
            ->where('tournament_id', $tournament->id)
            ->where('round_no', $roundNo)
            ->where('status', '!=', 'completed')
            ->exists();
    }

    public function nextRoundExists(Tournament $tournament, int $roundNo): bool
    {
        return TournamentMatch::query()
            ->where('tournament_id', $tournament->id)
            ->where('round_no', $roundNo)
            ->exists();
    }

    public function resolveRoundWinners(Tournament $tournament, int $roundNo): Collection
    {
        $winnerEntryIds = TournamentMatch::query()
            ->where('tournament_id', $tournament->id)
            ->where('round_no', $roundNo)
            ->whereNotNull('winner_entry_id')
            ->orderBy('match_no')
            ->pluck('winner_entry_id');

        if ($winnerEntryIds->isEmpty()) {
            return collect();
        }

        return $tournament->entries()
            ->whereIn('id', $winnerEntryIds->all())
            ->orderBy('entry_no')
            ->get();
    }

    public function resolveActiveMatchForEntry(Tournament $tournament, TournamentEntry $entry): ?TournamentMatch
    {
        return TournamentMatch::query()
            ->where('tournament_id', $tournament->id)
            ->where('status', '!=', 'completed')
            ->whereHas('entries', function ($query) use ($entry) {
                $query->where('tournament_entry_id', $entry->id)
                    ->where('status', '!=', 'completed');
            })
            ->orderByDesc('round_no')
            ->orderBy('match_no')
            ->first();
    }
}
