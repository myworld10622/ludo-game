<?php

namespace App\Http\Controllers\Admin\Web;

use App\Http\Controllers\Controller;
use App\Models\TournamentMatch;
use App\Models\TournamentMatchPlayer;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $query = User::query()
            ->withCount(['tournamentRegistrations as matches_played'])
            ->with('primaryWallet')
            ->latest();

        if ($search = $request->input('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('username', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('user_code', $search);
            });
        }

        return view('admin.users.index', [
            'users' => $query->paginate(25)->withQueryString(),
        ]);
    }

    public function show(User $user): View
    {
        $user->loadCount(['tournamentRegistrations as matches_played']);
        $user->load('primaryWallet', 'profile');

        $registrations = $user->tournamentRegistrations()
            ->with(['tournament', 'matchPlayers.match'])
            ->latest()
            ->paginate(20);

        $ownedTournaments = $user->tournaments()
            ->with('prizes')
            ->withCount([
                'registrations',
                'matches as completed_matches_count' => fn ($q) => $q->where('status', 'completed'),
                'matches as pending_matches_count' => fn ($q) => $q->whereIn('status', ['scheduled', 'waiting', 'in_progress', 'disputed', 'forfeited']),
            ])
            ->latest()
            ->get();

        return view('admin.users.show', compact('user', 'registrations', 'ownedTournaments'));
    }

    public function updatePanelPermissions(Request $request, User $user): RedirectResponse
    {
        $permissions = [];

        foreach (User::defaultPanelPermissions() as $key => $enabled) {
            $permissions[$key] = $request->boolean("permissions.{$key}");
        }

        $user->updatePanelPermissions($permissions);

        return redirect()
            ->route('admin.users.show', $user)
            ->with('status', 'User panel permissions updated successfully.');
    }

    // GET /admin/users/{user}/matches  — returns HTML fragment for popup
    public function userMatches(User $user): View
    {
        $players = TournamentMatchPlayer::query()
            ->whereHas('registration', fn ($q) => $q->where('user_id', $user->id))
            ->with([
                'match.tournament',
                'match.winner.user',
                'registration',
            ])
            ->orderByDesc('id')
            ->limit(50)
            ->get();

        return view('admin.users.matches_popup', compact('user', 'players'));
    }
}
