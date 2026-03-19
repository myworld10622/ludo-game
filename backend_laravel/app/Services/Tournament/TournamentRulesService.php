<?php

namespace App\Services\Tournament;

use InvalidArgumentException;

class TournamentRulesService
{
    public function validateDefinition(array $payload): void
    {
        $allowMultipleEntries = (bool) ($payload['allow_multiple_entries'] ?? false);
        $maxEntriesPerUser = (int) ($payload['max_entries_per_user'] ?? 1);
        $minTotalEntries = (int) ($payload['min_total_entries'] ?? 2);
        $maxTotalEntries = isset($payload['max_total_entries']) ? (int) $payload['max_total_entries'] : null;

        if (! $allowMultipleEntries && $maxEntriesPerUser > 1) {
            throw new InvalidArgumentException('max_entries_per_user must be 1 when multiple entries are disabled.');
        }

        if ($maxEntriesPerUser < 1) {
            throw new InvalidArgumentException('max_entries_per_user must be at least 1.');
        }

        if ($maxTotalEntries !== null && $minTotalEntries > $maxTotalEntries) {
            throw new InvalidArgumentException('min_total_entries cannot exceed max_total_entries.');
        }
    }

    public function validatePrizeRules(array $prizes): void
    {
        $normalized = [];

        foreach ($prizes as $prize) {
            $from = (int) ($prize['rank_from'] ?? 0);
            $to = (int) ($prize['rank_to'] ?? 0);

            if ($from < 1 || $to < $from) {
                throw new InvalidArgumentException('Invalid prize rank range.');
            }

            $normalized[] = [$from, $to];
        }

        usort($normalized, static fn (array $a, array $b) => $a[0] <=> $b[0]);

        for ($i = 1; $i < count($normalized); $i++) {
            if ($normalized[$i][0] <= $normalized[$i - 1][1]) {
                throw new InvalidArgumentException('Prize rank ranges cannot overlap.');
            }
        }
    }
}
