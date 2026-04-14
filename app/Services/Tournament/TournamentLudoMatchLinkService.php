<?php

namespace App\Services\Tournament;

use App\Models\Tournament;
use App\Models\TournamentMatchLink;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class TournamentLudoMatchLinkService
{
    public function __construct(
        private readonly TournamentBracketConfigService $tournamentBracketConfigService,
        private readonly TournamentBracketMatchService $tournamentBracketMatchService
    ) {
    }

    public function seedRoundOne(Tournament $tournament, int $tableSize = 4): Collection
    {
        $entries = $tournament->entries()
            ->whereIn('status', ['joined', 'checked_in'])
            ->orderBy('entry_no')
            ->get();

        $resolvedTableSize = $tableSize > 0
            ? $tableSize
            : $this->tournamentBracketConfigService->resolveMatchSize($tournament);

        return $this->seedRound($tournament, $entries, 1, $resolvedTableSize);
    }

    public function seedRound(Tournament $tournament, Collection $entries, int $roundNo, int $tableSize = 4): Collection
    {
        $resolvedTableSize = $tableSize > 0
            ? $tableSize
            : $this->tournamentBracketConfigService->resolveMatchSize($tournament);
        $configSummary = $this->tournamentBracketConfigService->buildSummary($tournament, $entries->count());

        return DB::transaction(function () use ($tournament, $entries, $roundNo, $resolvedTableSize, $configSummary): Collection {
            $matches = $this->tournamentBracketMatchService->seedRound(
                $tournament,
                $entries,
                $roundNo,
                $resolvedTableSize,
                $configSummary['advance_count']
            )->keyBy('match_no');
            $linkRows = [];
            $now = now();

            foreach ($matches as $tableNo => $match) {
                $matchEntries = $match->entries
                    ->filter(fn ($matchEntry) => $matchEntry->status !== 'completed')
                    ->values();

                if ($match->status === 'completed') {
                    continue;
                }

                foreach ($matchEntries as $matchEntry) {
                    $entry = $entries->firstWhere('id', $matchEntry->tournament_entry_id);
                    if (! $entry) {
                        continue;
                    }

                $linkRows[] = [
                    'tournament_id' => $tournament->id,
                    'tournament_entry_id' => $entry->id,
                    'game_match_id' => null,
                    'external_match_uuid' => null,
                    'round_no' => $roundNo,
                    'table_no' => $tableNo,
                    'status' => 'assigned',
                    'meta' => json_encode([
                        'game' => 'ludo',
                        'table_size' => $match->max_players ?? $resolvedTableSize,
                        'round_no' => $roundNo,
                        'advance_count' => $configSummary['advance_count'],
                        'tournament_match_id' => $match->id,
                        'tournament_match_uuid' => $match->match_uuid,
                    ], JSON_THROW_ON_ERROR),
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];

                    $entry->status = 'seeded';
                    $entry->completed_at = null;
                    $entry->save();
                }
            }

            if (! empty($linkRows)) {
                TournamentMatchLink::query()->upsert(
                    $linkRows,
                    ['tournament_id', 'tournament_entry_id', 'round_no', 'table_no'],
                    ['status', 'meta', 'updated_at']
                );
            }

            $tournament->match_size = $configSummary['match_size'];
            $tournament->advance_count = $configSummary['advance_count'];
            $tournament->bracket_size = $configSummary['bracket_size'] ?: $tournament->bracket_size;
            $tournament->bye_count = $configSummary['bye_count'];
            $tournament->seeding_strategy = $configSummary['seeding_strategy'];
            $tournament->bot_fill_policy = $configSummary['bot_fill_policy'];
            $tournament->save();

            return TournamentMatchLink::query()
                ->where('tournament_id', $tournament->id)
                ->where('round_no', $roundNo)
                ->orderBy('table_no')
                ->orderBy('id')
                ->get();
        });
    }
}
