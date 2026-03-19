<?php

namespace App\Services\Tournament;

use App\Models\GameRoom;
use App\Models\Tournament;
use App\Models\TournamentMatch;

class TournamentHealthService
{
    public function summarize(Tournament $tournament): array
    {
        $matches = TournamentMatch::query()
            ->where('tournament_id', $tournament->id)
            ->get();

        $rounds = $matches
            ->groupBy('round_no')
            ->map(function ($roundMatches, $roundNo) {
                return [
                    'round_no' => (int) $roundNo,
                    'total_matches' => $roundMatches->count(),
                    'completed_matches' => $roundMatches->where('status', 'completed')->count(),
                    'pending_matches' => $roundMatches->where('status', 'pending')->count(),
                    'assigned_matches' => $roundMatches->where('status', 'assigned')->count(),
                    'waiting_room_matches' => $roundMatches->filter(fn ($match) => ! empty($match->external_match_ref) && $match->status !== 'completed')->count(),
                ];
            })
            ->sortBy('round_no')
            ->values();

        $activeRooms = GameRoom::query()
            ->where('meta->tournament_id', (string) $tournament->id)
            ->whereIn('status', ['waiting', 'starting', 'playing'])
            ->count();

        $stuckMatches = $matches
            ->filter(function ($match) {
                return $match->status !== 'completed'
                    && ! empty($match->external_match_ref)
                    && $match->updated_at
                    && $match->updated_at->lt(now()->subMinutes(10));
            })
            ->values()
            ->map(fn ($match) => [
                'id' => $match->id,
                'match_uuid' => $match->match_uuid,
                'round_no' => $match->round_no,
                'match_no' => $match->match_no,
                'status' => $match->status,
                'external_match_ref' => $match->external_match_ref,
                'updated_at' => optional($match->updated_at)->toIso8601String(),
            ]);

        return [
            'tournament_id' => $tournament->id,
            'tournament_uuid' => $tournament->uuid,
            'status' => $tournament->status,
            'current_total_entries' => (int) $tournament->current_total_entries,
            'current_active_entries' => (int) $tournament->current_active_entries,
            'total_matches' => $matches->count(),
            'completed_matches' => $matches->where('status', 'completed')->count(),
            'active_rooms' => $activeRooms,
            'rounds' => $rounds,
            'stuck_matches' => $stuckMatches,
        ];
    }
}
