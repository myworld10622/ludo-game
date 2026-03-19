<?php

namespace App\Services\Tournament;

use App\Models\Tournament;

class TournamentBracketConfigService
{
    public function resolveMatchSize(Tournament $tournament): int
    {
        $rules = (array) ($tournament->rules ?? []);
        $meta = (array) ($tournament->meta ?? []);

        $matchSize = (int) (
            $tournament->match_size
            ?? $rules['players_per_match']
            ?? $meta['players_per_match']
            ?? $meta['max_players']
            ?? $tournament->max_players
            ?? 4
        );

        return in_array($matchSize, [2, 4], true) ? $matchSize : 4;
    }

    public function resolveAdvanceCount(Tournament $tournament): int
    {
        $rules = (array) ($tournament->rules ?? []);
        $meta = (array) ($tournament->meta ?? []);
        $matchSize = $this->resolveMatchSize($tournament);

        $advanceCount = (int) (
            $tournament->advance_count
            ?? $rules['advance_count']
            ?? $meta['advance_count']
            ?? 1
        );

        return max(1, min($matchSize - 1, $advanceCount));
    }

    public function resolveBracketSize(Tournament $tournament, ?int $entryCount = null): int
    {
        $configuredBracketSize = (int) ($tournament->bracket_size ?? 0);
        $entryCount ??= (int) ($tournament->max_total_entries ?? $tournament->current_total_entries ?? $tournament->min_total_entries ?? 0);

        if ($configuredBracketSize > 0 && $configuredBracketSize >= $entryCount) {
            return $configuredBracketSize;
        }

        if ($entryCount <= 0) {
            return 0;
        }

        $base = $this->resolveMatchSize($tournament);
        $bracketSize = $base;

        while ($bracketSize < $entryCount) {
            $bracketSize *= $base;
        }

        return $bracketSize;
    }

    public function resolveByeCount(Tournament $tournament, ?int $entryCount = null): int
    {
        $entryCount ??= (int) ($tournament->current_total_entries ?? $tournament->max_total_entries ?? 0);

        if ($entryCount <= 0) {
            return max(0, (int) ($tournament->bye_count ?? 0));
        }

        return max(0, $this->resolveBracketSize($tournament, $entryCount) - $entryCount);
    }

    public function resolveSeedingStrategy(Tournament $tournament): string
    {
        return (string) ($tournament->seeding_strategy ?: 'random');
    }

    public function resolveBotFillPolicy(Tournament $tournament): string
    {
        return (string) ($tournament->bot_fill_policy ?: 'fill_after_timeout');
    }

    public function buildSummary(Tournament $tournament, ?int $entryCount = null): array
    {
        $entryCount ??= (int) ($tournament->current_total_entries ?? $tournament->max_total_entries ?? 0);

        return [
            'match_size' => $this->resolveMatchSize($tournament),
            'advance_count' => $this->resolveAdvanceCount($tournament),
            'bracket_size' => $this->resolveBracketSize($tournament, $entryCount),
            'bye_count' => $this->resolveByeCount($tournament, $entryCount),
            'seeding_strategy' => $this->resolveSeedingStrategy($tournament),
            'bot_fill_policy' => $this->resolveBotFillPolicy($tournament),
        ];
    }
}
