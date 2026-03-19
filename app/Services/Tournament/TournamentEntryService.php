<?php

namespace App\Services\Tournament;

use App\Models\Tournament;
use App\Models\TournamentEntry;
use Illuminate\Support\Str;

class TournamentEntryService
{
    public function generateTicketNumber(Tournament $tournament, int $entryNo): string
    {
        $prefix = $tournament->ticket_prefix ?: Str::upper(
            $tournament->game->code
            ?? $tournament->game->slug
            ?? $tournament->game->name
            ?? 'TMT'
        );

        return sprintf('%s-%06d', $prefix, $entryNo);
    }

    public function nextEntryIndexForUser(Tournament $tournament, int $userId): int
    {
        return (int) $tournament
            ->entries()
            ->where('user_id', $userId)
            ->count() + 1;
    }

    public function canJoin(Tournament $tournament, int $userId): bool
    {
        if (! $tournament->allow_multiple_entries) {
            return ! $tournament->entries()->where('user_id', $userId)->exists();
        }

        return $tournament->entries()->where('user_id', $userId)->count() < $tournament->max_entries_per_user;
    }
}
