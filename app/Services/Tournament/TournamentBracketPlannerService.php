<?php

namespace App\Services\Tournament;

use App\Models\Tournament;
use Illuminate\Support\Collection;

class TournamentBracketPlannerService
{
    public function planRound(Tournament $tournament, Collection $entries, int $roundNo, int $tableSize): Collection
    {
        $seededEntries = $this->seedEntries($tournament, $entries, $roundNo);
        $matchSizes = $this->resolveMatchSizes($seededEntries->count(), $tableSize);
        $plans = collect();
        $offset = 0;

        foreach ($matchSizes as $index => $assignedCount) {
            $chunk = $seededEntries->slice($offset, $assignedCount)->values();
            $offset += $assignedCount;

            $plans->push([
                'match_no' => $index + 1,
                'bracket_position' => $index + 1,
                'match_size' => $assignedCount,
                'assigned_count' => $chunk->count(),
                'bye_slots' => max(0, $tableSize - $chunk->count()),
                'entries' => $chunk->values(),
            ]);
        }

        return $plans;
    }

    public function resolveMatchSizes(int $entryCount, int $tableSize): array
    {
        if ($entryCount <= 0) {
            return [];
        }

        if ($entryCount === 1) {
            return [1];
        }

        $matchCount = (int) ceil($entryCount / $tableSize);

        // Ensure no match drops below 2 players. Reduce match count if needed.
        while ($matchCount > 1 && $entryCount < ($matchCount * 2)) {
            $matchCount--;
        }

        // Start with minimum 2 per match, then distribute the remaining players.
        $sizes = array_fill(0, $matchCount, 2);
        $remaining = $entryCount - ($matchCount * 2);

        $index = 0;
        while ($remaining > 0) {
            if ($sizes[$index] < $tableSize) {
                $sizes[$index]++;
                $remaining--;
            }
            $index = ($index + 1) % $matchCount;
        }

        return $sizes;
    }

    private function seedEntries(Tournament $tournament, Collection $entries, int $roundNo): Collection
    {
        $strategy = (string) ($tournament->seeding_strategy ?: 'random');
        $values = $entries->values();

        if ($roundNo > 1) {
            return $values;
        }

        return match ($strategy) {
            'ranked' => $values->sortBy('entry_no')->values(),
            'random' => $values->sortBy(function ($entry) {
                return sprintf('%010d-%s', crc32((string) $entry->uuid), (string) $entry->uuid);
            })->values(),
            default => $values,
        };
    }
}
