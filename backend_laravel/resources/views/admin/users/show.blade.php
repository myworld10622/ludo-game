@extends('admin.layouts.app')

@section('title', $user->username . ' — User Detail')
@section('heading', $user->username)
@section('subheading', 'User code: ' . $user->user_code . ' · Full profile, tournaments, reports, and match history')

@section('content')
<div class="stack">
    <div>
        <a href="{{ route('admin.users.index') }}" style="color:#2563eb;font-size:14px;">← Back to Users</a>
    </div>

    <div class="panel">
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:16px;">
            <div>
                <div class="stat-label">User Code</div>
                <div style="font-family:monospace;font-size:20px;font-weight:700;letter-spacing:2px;">{{ $user->user_code }}</div>
            </div>
            <div>
                <div class="stat-label">Username</div>
                <div style="font-size:18px;font-weight:700;">{{ $user->username }}</div>
            </div>
            <div>
                <div class="stat-label">Email</div>
                <div>{{ $user->email ?: '—' }}</div>
            </div>
            <div>
                <div class="stat-label">Mobile</div>
                <div>{{ $user->mobile ?: '—' }}</div>
            </div>
            <div>
                <div class="stat-label">Wallet Balance</div>
                <div style="font-size:20px;font-weight:700;color:#065f46;">₹{{ $user->primaryWallet ? number_format($user->primaryWallet->balance, 2) : '0.00' }}</div>
            </div>
            <div>
                <div class="stat-label">Matches Played</div>
                <div style="font-size:20px;font-weight:700;">{{ $user->matches_played }}</div>
            </div>
            <div>
                <div class="stat-label">Owned Tournaments</div>
                <div style="font-size:20px;font-weight:700;">{{ $ownedTournaments->count() }}</div>
            </div>
            <div>
                <div class="stat-label">Joined</div>
                <div>{{ $user->created_at->format('M d, Y') }}</div>
            </div>
        </div>
    </div>

    <div class="panel">
        <div style="display:flex;justify-content:space-between;gap:14px;align-items:flex-start;flex-wrap:wrap;margin-bottom:14px;">
            <div>
                <div style="font-size:18px;font-weight:700;">Edit User Details</div>
                <div class="muted" style="margin-top:4px;">Update core account fields, profile details, and account status from one place.</div>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.users.update', $user) }}" class="stack">
            @csrf
            @method('PUT')

            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:16px;">
                <div>
                    <label>User Code</label>
                    <input type="text" name="user_code" value="{{ old('user_code', $user->user_code) }}" maxlength="8">
                </div>
                <div>
                    <label>Username</label>
                    <input type="text" name="username" value="{{ old('username', $user->username) }}">
                </div>
                <div>
                    <label>Email</label>
                    <input type="email" name="email" value="{{ old('email', $user->email) }}">
                </div>
                <div>
                    <label>Mobile</label>
                    <input type="text" name="mobile" value="{{ old('mobile', $user->mobile) }}">
                </div>
                <div>
                    <label>Referral Code</label>
                    <input type="text" name="referral_code" value="{{ old('referral_code', $user->referral_code) }}">
                </div>
                <div>
                    <label>Language</label>
                    <input type="text" name="language" value="{{ old('language', $user->profile?->language) }}">
                </div>
                <div>
                    <label>First Name</label>
                    <input type="text" name="first_name" value="{{ old('first_name', $user->profile?->first_name) }}">
                </div>
                <div>
                    <label>Last Name</label>
                    <input type="text" name="last_name" value="{{ old('last_name', $user->profile?->last_name) }}">
                </div>
                <div>
                    <label>Date of Birth</label>
                    <input type="date" name="date_of_birth" value="{{ old('date_of_birth', $user->profile?->date_of_birth?->format('Y-m-d')) }}">
                </div>
                <div>
                    <label>Gender</label>
                    <select name="gender">
                        @php($genderValue = old('gender', $user->profile?->gender))
                        <option value="">Select gender</option>
                        <option value="male" {{ $genderValue === 'male' ? 'selected' : '' }}>Male</option>
                        <option value="female" {{ $genderValue === 'female' ? 'selected' : '' }}>Female</option>
                        <option value="other" {{ $genderValue === 'other' ? 'selected' : '' }}>Other</option>
                    </select>
                </div>
                <div>
                    <label>Country Code</label>
                    <input type="text" name="country_code" value="{{ old('country_code', $user->profile?->country_code) }}">
                </div>
                <div>
                    <label>State</label>
                    <input type="text" name="state" value="{{ old('state', $user->profile?->state) }}">
                </div>
                <div>
                    <label>City</label>
                    <input type="text" name="city" value="{{ old('city', $user->profile?->city) }}">
                </div>
                <div style="grid-column:span 2;">
                    <label>Avatar URL</label>
                    <input type="url" name="avatar_url" value="{{ old('avatar_url', $user->profile?->avatar_url) }}">
                </div>
            </div>

            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:16px;">
                <div>
                    <label>New Password</label>
                    <input type="password" name="password" placeholder="Leave blank to keep current password">
                </div>
                <div>
                    <label>Confirm New Password</label>
                    <input type="password" name="password_confirmation" placeholder="Repeat new password">
                </div>
            </div>

            <div style="display:flex;gap:18px;flex-wrap:wrap;">
                <label style="display:flex;align-items:center;gap:10px;margin:0;">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', $user->is_active) ? 'checked' : '' }} style="width:auto;">
                    <span>Active Account</span>
                </label>
                <label style="display:flex;align-items:center;gap:10px;margin:0;">
                    <input type="checkbox" name="is_banned" value="1" {{ old('is_banned', $user->is_banned) ? 'checked' : '' }} style="width:auto;">
                    <span>Banned</span>
                </label>
            </div>

            <div>
                <button type="submit" class="btn">Save User Details</button>
            </div>
        </form>
    </div>

    <div class="panel">
        <div style="display:flex;justify-content:space-between;gap:14px;align-items:flex-start;flex-wrap:wrap;margin-bottom:14px;">
            <div>
                <div style="font-size:18px;font-weight:700;">User Panel Permission Matrix</div>
                <div class="muted" style="margin-top:4px;">Enable or hide modules for this specific user panel.</div>
            </div>
        </div>
        @php($panelPermissions = $user->panelPermissions())
        @php($permissionGroups = [
            'Panel Access' => [
                'view_panel' => 'Panel dashboard access',
            ],
            'Tournament Controls' => [
                'manage_tournaments' => 'Create and update tournaments',
                'approve_tournaments' => 'Approve own tournaments',
                'force_live' => 'Force tournament live',
                'manage_fake_registrations' => 'Set fake registration count',
            ],
            'Match Controls' => [
                'view_match_monitor' => 'View match monitor',
                'force_match_winner' => 'Set manual winner',
            ],
        ])
        <form method="POST" action="{{ route('admin.users.panel-permissions', $user) }}">
            @csrf
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:16px;">
                @foreach($permissionGroups as $group => $items)
                    <div style="border:1px solid #d9e1e7;border-radius:14px;overflow:hidden;">
                        <div style="padding:12px 14px;background:#f8fafc;border-bottom:1px solid #d9e1e7;font-weight:700;">{{ $group }}</div>
                        <div style="padding:12px;display:grid;gap:10px;">
                            @foreach($items as $key => $label)
                                <label style="display:flex;justify-content:space-between;align-items:flex-start;gap:12px;padding:12px;border:1px solid #e5e7eb;border-radius:12px;">
                                    <span>
                                        <div style="font-weight:600;">{{ $label }}</div>
                                        <div class="muted" style="font-size:12px;margin-top:3px;">{{ $key }}</div>
                                    </span>
                                    <input type="checkbox" name="permissions[{{ $key }}]" value="1" {{ !empty($panelPermissions[$key]) ? 'checked' : '' }}>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
            <div style="margin-top:16px;">
                <button type="submit" class="btn">Save Permission Matrix</button>
            </div>
        </form>
    </div>

    <div class="panel" style="padding:0;overflow:hidden;">
        <div style="padding:18px 18px 0;">
            <div style="font-size:18px;font-weight:700;">User-Owned Tournament Reports</div>
            <div class="muted" style="margin-top:4px;">Open any tournament to see full report with winners, registrations, matches, and financials.</div>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Tournament</th>
                        <th>Created</th>
                        <th>Status</th>
                        <th>Players</th>
                        <th>Matches</th>
                        <th>Prize Pool</th>
                        <th>Report</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($ownedTournaments as $tournament)
                    <tr>
                        <td>
                            <strong>{{ $tournament->name }}</strong>
                            <div class="muted" style="font-size:12px;">{{ ucfirst($tournament->type) }} · {{ ucwords(str_replace('_', ' ', $tournament->format)) }}</div>
                        </td>
                        <td>{{ $tournament->created_at?->format('M d, Y h:i A') ?? '—' }}</td>
                        <td>{{ ucwords(str_replace('_', ' ', $tournament->status)) }}</td>
                        <td>{{ $tournament->registrations_count }}/{{ $tournament->max_players }}</td>
                        <td>{{ $tournament->completed_matches_count }} complete · {{ $tournament->pending_matches_count }} pending</td>
                        <td>₹{{ number_format((float) $tournament->total_prize_pool, 2) }}</td>
                        <td><a href="{{ route('admin.tournaments.report', $tournament) }}" style="color:#2563eb;font-weight:600;">Open Report</a></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="muted">This user has not created any tournaments yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div style="font-size:16px;font-weight:700;margin-top:8px;">Tournament Registrations</div>
    <div class="panel" style="padding:0;overflow:hidden;">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Tournament</th>
                        <th>Status</th>
                        <th>Entry Fee</th>
                        <th>Position</th>
                        <th>Prize Won</th>
                        <th>Registered</th>
                        <th>Eliminated</th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($registrations as $reg)
                    <tr>
                        <td style="font-weight:600;max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $reg->tournament?->name ?? "T#{$reg->tournament_id}" }}</td>
                        <td>{{ ucfirst($reg->status) }}</td>
                        <td>₹{{ number_format($reg->entry_fee_paid, 2) }}</td>
                        <td>{{ $reg->final_position ? '#' . $reg->final_position : '—' }}</td>
                        <td>@if($reg->prize_won > 0)<strong style="color:#065f46;">₹{{ number_format($reg->prize_won, 2) }}</strong>@else<span class="muted">—</span>@endif</td>
                        <td class="muted" style="font-size:12px;">{{ $reg->registered_at?->format('M d, Y') ?? '—' }}</td>
                        <td class="muted" style="font-size:12px;">{{ $reg->eliminated_at?->format('M d H:i') ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="muted">No tournament registrations yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div style="padding:16px 18px;">{{ $registrations->links() }}</div>
    </div>
</div>
@endsection
