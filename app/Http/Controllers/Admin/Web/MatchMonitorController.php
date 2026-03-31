<?php

namespace App\Http\Controllers\Admin\Web;

use App\Http\Controllers\Controller;
use App\Models\TournamentMatch;
use App\Models\TournamentRegistration;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MatchMonitorController extends Controller
{
    public function index(): View
    {
        // Running matches (scheduled / waiting / in_progress) across all tournaments
        $runningMatches = TournamentMatch::with([
                'tournament',
                'players.registration.user',
            ])
            ->whereIn('status', ['scheduled', 'waiting', 'in_progress'])
            ->orderBy('tournament_id')
            ->orderBy('round_number')
            ->orderBy('match_number')
            ->get();

        // Recently completed matches (latest 100)
        $completedMatches = TournamentMatch::with([
                'tournament',
                'winner.user',
                'players.registration.user',
            ])
            ->where('status', 'completed')
            ->orderByDesc('ended_at')
            ->limit(100)
            ->get();

        // Stats
        $stats = [
            'running'         => $runningMatches->count(),
            'completed_total' => TournamentMatch::where('status', 'completed')->count(),
            'overridden'      => TournamentMatch::where('is_admin_override', true)->count(),
            'tournaments_live' => TournamentMatch::whereIn('status', ['in_progress'])
                ->distinct('tournament_id')->count('tournament_id'),
        ];

        return view('admin.tournaments.matches', compact('runningMatches', 'completedMatches', 'stats'));
    }

    // POST /admin/matches/{match}/force-winner
    public function forceWinner(Request $request, TournamentMatch $match): RedirectResponse
    {
        $request->validate([
            'registration_id' => ['required', 'exists:tournament_registrations,id'],
            'note'            => ['nullable', 'string', 'max:255'],
        ]);

        // Verify registration belongs to this match
        $valid = $match->players()->where('registration_id', $request->registration_id)->exists();
        if (! $valid) {
            return back()->with('error', 'That player is not in this match.');
        }

        $match->update([
            'forced_winner_registration_id' => $request->registration_id,
            'is_admin_override'             => true,
            'admin_override_note'           => $request->note ?: 'Admin forced winner pre-set.',
        ]);

        $reg  = TournamentRegistration::find($request->registration_id);
        $name = $reg?->is_bot ? ($reg->bot_name ?? 'Bot') : ($reg->user?->username ?? 'User');

        return back()->with('status', "✅ Winner pre-set to \"{$name}\" for Match #{$match->match_number} (R{$match->round_number}). Will apply when game ends.");
    }
}
