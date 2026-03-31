<?php

namespace App\Support;

use App\Models\Tournament;
use App\Models\TournamentMatch;
use App\Models\TournamentRegistration;

class TournamentReportBuilder
{
    public static function build(Tournament $tournament): array
    {
        $tournament->loadMissing([
            'creator',
            'prizes.winner',
            'registrations.user',
            'walletTransactions.user',
            'matches.players.registration.user',
            'matches.winner.user',
            'matches.forcedWinner.user',
        ]);

        $matches = $tournament->matches->sortBy([
            ['round_number', 'asc'],
            ['match_number', 'asc'],
        ])->values();

        $registrations = $tournament->registrations
            ->sortBy([
                ['final_position', 'asc'],
                ['registered_at', 'asc'],
            ])
            ->values();

        $prizes = $tournament->prizes->sortBy('position')->values();

        $stats = [
            'total_players' => $registrations->count(),
            'real_players' => $registrations->where('is_bot', false)->count(),
            'bot_players' => $registrations->where('is_bot', true)->count(),
            'completed_matches' => $matches->where('status', TournamentMatch::STATUS_COMPLETED)->count(),
            'pending_matches' => $matches->whereIn('status', [
                TournamentMatch::STATUS_SCHEDULED,
                TournamentMatch::STATUS_WAITING,
                TournamentMatch::STATUS_IN_PROGRESS,
                TournamentMatch::STATUS_DISPUTED,
                TournamentMatch::STATUS_FORFEITED,
            ])->count(),
            'cancelled_matches' => $matches->where('status', TournamentMatch::STATUS_CANCELLED)->count(),
            'winner_count' => $registrations->where('status', TournamentRegistration::STATUS_WINNER)->count(),
            'eliminated_count' => $registrations->where('status', TournamentRegistration::STATUS_ELIMINATED)->count(),
            'gross_entry' => round($registrations->sum(fn ($reg) => (float) $reg->entry_fee_paid), 2),
            'prize_paid' => round($prizes->sum(fn ($prize) => (float) $prize->prize_amount), 2),
            'override_matches' => $matches->where('is_admin_override', true)->count(),
        ];

        $rounds = $matches->groupBy('round_number')->map(function ($roundMatches, $roundNumber) {
            return [
                'round_number' => (int) $roundNumber,
                'total_matches' => $roundMatches->count(),
                'completed_matches' => $roundMatches->where('status', TournamentMatch::STATUS_COMPLETED)->count(),
                'pending_matches' => $roundMatches->whereIn('status', [
                    TournamentMatch::STATUS_SCHEDULED,
                    TournamentMatch::STATUS_WAITING,
                    TournamentMatch::STATUS_IN_PROGRESS,
                    TournamentMatch::STATUS_DISPUTED,
                    TournamentMatch::STATUS_FORFEITED,
                ])->count(),
                'cancelled_matches' => $roundMatches->where('status', TournamentMatch::STATUS_CANCELLED)->count(),
                'matches' => $roundMatches->values(),
            ];
        })->values();

        $financialRows = $tournament->walletTransactions
            ->sortByDesc('created_at')
            ->values();

        return compact('tournament', 'stats', 'rounds', 'registrations', 'prizes', 'financialRows');
    }
}
