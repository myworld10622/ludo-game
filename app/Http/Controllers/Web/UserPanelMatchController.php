<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\TournamentMatch;
use App\Models\TournamentRegistration;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class UserPanelMatchController extends Controller
{
    public function index(): View
    {
        $user = $this->user();
        $this->ensurePermission('view_match_monitor');

        $baseQuery = TournamentMatch::query()->whereHas('tournament', function ($query) use ($user) {
            $query->where('creator_type', 'user')->where('creator_user_id', $user->id);
        });

        $runningMatches = (clone $baseQuery)
            ->with(['tournament', 'players.registration.user'])
            ->whereIn('status', ['scheduled', 'waiting', 'in_progress'])
            ->orderBy('tournament_id')
            ->orderBy('round_number')
            ->orderBy('match_number')
            ->get();

        $completedMatches = (clone $baseQuery)
            ->with(['tournament', 'winner.user', 'players.registration.user'])
            ->where('status', 'completed')
            ->orderByDesc('ended_at')
            ->limit(100)
            ->get();

        $stats = [
            'running' => $runningMatches->count(),
            'completed_total' => (clone $baseQuery)->where('status', 'completed')->count(),
            'overridden' => (clone $baseQuery)->where('is_admin_override', true)->count(),
            'tournaments_live' => (clone $baseQuery)->whereIn('status', ['in_progress'])->distinct('tournament_id')->count('tournament_id'),
        ];

        return view('user.matches.index', compact('runningMatches', 'completedMatches', 'stats'));
    }

    public function forceWinner(Request $request, TournamentMatch $match): RedirectResponse
    {
        $user = $this->user();
        $this->ensurePermission('force_match_winner');
        abort_unless(
            $match->tournament && $match->tournament->creator_type === 'user' && (int) $match->tournament->creator_user_id === (int) $user->id,
            403,
            'You cannot manage this match.'
        );

        $request->validate([
            'registration_id' => ['required', 'exists:tournament_registrations,id'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        $valid = $match->players()->where('registration_id', $request->registration_id)->exists();
        if (! $valid) {
            return back()->with('error', 'That player is not in this match.');
        }

        $match->update([
            'forced_winner_registration_id' => $request->registration_id,
            'is_admin_override' => true,
            'admin_override_note' => $request->note ?: 'User panel winner pre-set.',
        ]);

        $reg = TournamentRegistration::find($request->registration_id);
        $name = $reg?->is_bot ? ($reg->bot_name ?? 'Bot') : ($reg->user?->username ?? 'User');

        return back()->with('status', "Winner pre-set to \"{$name}\" for Match #{$match->match_number}.");
    }

    private function ensurePermission(string $permission): void
    {
        abort_unless($this->user()->hasPanelPermission($permission), 403, 'This panel option is disabled by admin.');
    }

    private function user(): User
    {
        /** @var User $user */
        $user = Auth::guard('web')->user();
        return $user;
    }
}
