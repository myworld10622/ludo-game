<?php

namespace App\Http\Controllers\Admin\Web;

use App\Http\Controllers\Controller;
use App\Models\TournamentMatch;
use App\Models\TournamentMatchPlayer;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
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

    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'user_code' => [
                'required',
                'digits:8',
                Rule::unique('users', 'user_code')->ignore($user->id),
            ],
            'username' => [
                'required',
                'string',
                'max:255',
                Rule::unique('users', 'username')->ignore($user->id),
            ],
            'email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'mobile' => [
                'nullable',
                'string',
                'max:20',
                Rule::unique('users', 'mobile')->ignore($user->id),
            ],
            'referral_code' => ['nullable', 'string', 'max:50'],
            'password' => ['nullable', 'string', 'min:6', 'confirmed'],
            'is_active' => ['nullable', 'boolean'],
            'is_banned' => ['nullable', 'boolean'],
            'first_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'date_of_birth' => ['nullable', 'date'],
            'gender' => ['nullable', 'string', 'max:20'],
            'country_code' => ['nullable', 'string', 'max:10'],
            'state' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'language' => ['nullable', 'string', 'max:20'],
            'avatar_url' => ['nullable', 'url', 'max:2048'],
        ]);

        DB::transaction(function () use ($user, $validated, $request) {
            $user->fill([
                'user_code' => $validated['user_code'],
                'username' => $validated['username'],
                'email' => $validated['email'] ?: null,
                'mobile' => $validated['mobile'] ?: null,
                'referral_code' => $validated['referral_code'] ?: null,
                'is_active' => $request->boolean('is_active'),
                'is_banned' => $request->boolean('is_banned'),
            ]);

            if (!empty($validated['password'])) {
                $user->password = Hash::make($validated['password']);
            }

            $user->save();

            $profilePayload = [
                'first_name' => $validated['first_name'] ?: null,
                'last_name' => $validated['last_name'] ?: null,
                'date_of_birth' => $validated['date_of_birth'] ?: null,
                'gender' => $validated['gender'] ?: null,
                'country_code' => $validated['country_code'] ?: null,
                'state' => $validated['state'] ?: null,
                'city' => $validated['city'] ?: null,
                'language' => $validated['language'] ?: null,
                'avatar_url' => $validated['avatar_url'] ?: null,
            ];

            $profile = $user->profile ?: $user->profile()->create([]);
            $profile->fill($profilePayload)->save();
        });

        return redirect()
            ->route('admin.users.show', $user)
            ->with('status', 'User details updated successfully.');
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
