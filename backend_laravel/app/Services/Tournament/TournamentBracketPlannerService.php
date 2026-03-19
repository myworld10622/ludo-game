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

        $matchCount = (int) ceil($entryCount / $tableSize);
        $sizes = array_fill(0, $matchCount, $tableSize);
        $overflow = ($matchCount * $tableSize) - $entryCount;

        for ($i = $matchCount - 1; $i >= 0 && $overflow > 0; $i--) {
            $reducibleSeats = $sizes[$i] - 1;
            if ($reducibleSeats <= 0) {
                continue;
            }

            $reduction = min($overflow, $reducibleSeats);
            $sizes[$i] -= $reduction;
            $overflow -= $reduction;
        }

        return array_values(array_filter($sizes, static fn (int $size) => $size > 0));
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
