<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use App\Models\Tournament;
use App\Models\TournamentMatch;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class UserPanelAuthController extends Controller
{
    public function showLogin(): View|RedirectResponse
    {
        if (Auth::guard('web')->check()) {
            return redirect()->route('panel.index');
        }

        return view('user.auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'identity' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
        ]);

        $user = User::query()
            ->where('user_code', $validated['identity'])
            ->orWhere('username', $validated['identity'])
            ->orWhere('email', $validated['identity'])
            ->orWhere('mobile', $validated['identity'])
            ->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            return back()
                ->withErrors(['identity' => 'Invalid login credentials.'])
                ->withInput($request->only('identity'));
        }

        if (! $user->is_active || $user->is_banned) {
            return back()
                ->withErrors(['identity' => 'User account is not allowed to login.'])
                ->withInput($request->only('identity'));
        }

        Auth::guard('web')->login($user, true);
        $request->session()->regenerate();

        $user->forceFill([
            'last_login_at' => now(),
        ])->save();

        return redirect()->route('panel.index');
    }

    public function panel(): View
    {
        /** @var User $user */
        $user = Auth::guard('web')->user();
        $user->load('profile', 'primaryWallet');

        $ownedTournamentBase = Tournament::query()
            ->where('creator_type', 'user')
            ->where('creator_user_id', $user->id);

        $panelStats = [
            'pending_tournaments' => (clone $ownedTournamentBase)->where('is_approved', false)->count(),
            'rejected_tournaments' => (clone $ownedTournamentBase)->whereNotNull('rejected_at')->count(),
            'support_tickets' => SupportTicket::query()->where('user_id', $user->id)->count(),
            'live_tournaments' => (clone $ownedTournamentBase)->whereIn('status', [Tournament::STATUS_REGISTRATION_OPEN, Tournament::STATUS_IN_PROGRESS])->count(),
            'completed_tournaments' => (clone $ownedTournamentBase)->where('status', Tournament::STATUS_COMPLETED)->count(),
            'running_matches' => TournamentMatch::query()
                ->whereHas('tournament', fn ($query) => $query->where('creator_type', 'user')->where('creator_user_id', $user->id))
                ->whereIn('status', [TournamentMatch::STATUS_SCHEDULED, TournamentMatch::STATUS_WAITING, TournamentMatch::STATUS_IN_PROGRESS])
                ->count(),
        ];

        $recentTournamentAlerts = (clone $ownedTournamentBase)
            ->where(function ($query) {
                $query->where('is_approved', false)->orWhereNotNull('rejected_at');
            })
            ->latest()
            ->limit(5)
            ->get();

        $liveTournaments = (clone $ownedTournamentBase)
            ->withCount([
                'registrations',
                'matches as running_matches_count' => fn ($query) => $query->whereIn('status', [TournamentMatch::STATUS_SCHEDULED, TournamentMatch::STATUS_WAITING, TournamentMatch::STATUS_IN_PROGRESS]),
                'matches as completed_matches_count' => fn ($query) => $query->where('status', TournamentMatch::STATUS_COMPLETED),
            ])
            ->whereIn('status', [Tournament::STATUS_REGISTRATION_OPEN, Tournament::STATUS_IN_PROGRESS])
            ->latest()
            ->limit(6)
            ->get();

        $recentOwnedTournaments = (clone $ownedTournamentBase)
            ->withCount([
                'registrations',
                'matches as running_matches_count' => fn ($query) => $query->whereIn('status', [TournamentMatch::STATUS_SCHEDULED, TournamentMatch::STATUS_WAITING, TournamentMatch::STATUS_IN_PROGRESS]),
                'matches as completed_matches_count' => fn ($query) => $query->where('status', TournamentMatch::STATUS_COMPLETED),
            ])
            ->latest()
            ->limit(6)
            ->get();

        return view('user.panel.index', compact(
            'user',
            'panelStats',
            'recentTournamentAlerts',
            'liveTournaments',
            'recentOwnedTournaments'
        ));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('user.login');
    }
}
