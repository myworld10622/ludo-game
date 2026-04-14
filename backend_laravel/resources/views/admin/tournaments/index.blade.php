@extends('admin.layouts.app')

@section('title', 'Tournaments')
@section('heading', 'Tournaments')
@section('subheading', 'Create, monitor, and open full reports for admin and user tournaments')

@php
    $isEdit = (bool) $editingTournament;
    $formAction = $isEdit ? route('admin.tournaments.update', $editingTournament) : route('admin.tournaments.store');
    $t = $editingTournament;
    $existingPrizes = collect($t?->prizes ?? []);
    $prizePct = fn (int $pos) => old("prize_pct_{$pos}", $existingPrizes->firstWhere('position', $pos)?->prize_pct ?? match($pos) {1 => 50, 2 => 25, 3 => 15, 4 => 7, 5 => 3});
@endphp

@section('content')
<div class="stack">
    <div class="panel" style="background:linear-gradient(135deg,rgba(255,215,0,0.12),rgba(26,107,255,0.15));border-color:rgba(255,215,0,0.2);">
        <div style="display:flex;justify-content:space-between;gap:18px;align-items:flex-start;flex-wrap:wrap;">
            <div>
                <div class="badge" style="background:rgba(255,255,255,0.14);color:#fff;">Tournament Control Center</div>
                <h2 style="margin:12px 0 8px;font-size:30px;">Admin tournament reports and controls in one screen</h2>
                <div style="color:rgba(255,255,255,0.84);max-width:860px;line-height:1.7;">
                    Open any tournament to see full report like the user panel: created date, status, registrations, round-wise matches, winners, and financials.
                </div>
            </div>
            <form method="POST" action="{{ route('admin.tournaments.run-scheduler') }}">
                @csrf
                <button type="submit" class="btn" style="background:linear-gradient(135deg,var(--blue),#0033AA);"
                    onclick="return confirm('Run status scheduler now?\n\nThis will move tournaments based on current time.')">
                    Run Scheduler Now
                </button>
            </form>
        </div>
    </div>

    <div class="stats">
        <div class="stat-card"><div class="stat-label">Total Tournaments</div><div class="stat-value">{{ $tournamentStats['total'] }}</div></div>
        <div class="stat-card"><div class="stat-label">Admin Tournaments</div><div class="stat-value">{{ $tournamentStats['admin_total'] }}</div></div>
        <div class="stat-card"><div class="stat-label">User Tournaments</div><div class="stat-value">{{ $tournamentStats['user_total'] }}</div></div>
        <div class="stat-card"><div class="stat-label">Live</div><div class="stat-value">{{ $tournamentStats['live'] }}</div></div>
        <div class="stat-card"><div class="stat-label">Completed</div><div class="stat-value">{{ $tournamentStats['completed'] }}</div></div>
        <div class="stat-card"><div class="stat-label">Pending Approval</div><div class="stat-value">{{ $tournamentStats['pending_approval'] }}</div></div>
    </div>

    <div class="panel">
        <div class="header-row">
            <strong>Pending Approval Queue</strong>
            <span class="muted">{{ $pendingApprovalTournaments->count() }} waiting</span>
        </div>
        <div class="table-wrap responsive-table">
            <table>
                <thead>
                    <tr>
                        <th>Tournament</th>
                        <th>User</th>
                        <th>Created</th>
                        <th>Review</th>
                        <th>Edit</th>
                        <th>Approve</th>
                        <th>Reject With Reason</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($pendingApprovalTournaments as $tournament)
                        <tr>
                            <td data-label="Tournament">
                                <strong>{{ $tournament->name }}</strong>
                                <div class="muted" style="font-size:12px;">{{ ucfirst($tournament->type) }} · {{ ucwords(str_replace('_',' ', $tournament->format)) }}</div>
                            </td>
                            <td data-label="User">{{ $tournament->creator?->username ?? 'User' }}<div class="muted" style="font-size:12px;">{{ $tournament->creator?->user_code ?? '—' }}</div></td>
                            <td data-label="Created"><span data-utc-time="{{ $tournament->created_at?->toIso8601String() }}">{{ $tournament->created_at?->format('d M Y, h:i A') ?? '—' }}</span></td>
                            <td data-label="Review"><a href="{{ route('admin.tournaments.report', $tournament) }}" class="btn btn-secondary" style="font-size:12px;padding:6px 10px;">Review Details</a></td>
                            <td data-label="Edit"><a href="{{ route('admin.tournaments.edit', $tournament) }}" class="btn btn-secondary" style="font-size:12px;padding:6px 10px;">Edit Form</a></td>
                            <td data-label="Approve"><form method="POST" action="{{ route('admin.tournaments.approve', $tournament) }}">@csrf<button type="submit" class="btn" style="font-size:12px;padding:6px 10px;">Approve</button></form></td>
                            <td data-label="Reject" style="min-width:280px;">
                                <form method="POST" action="{{ route('admin.tournaments.reject', $tournament) }}" class="stack" style="gap:8px;">
                                    @csrf
                                    <textarea name="reason" rows="2" placeholder="Write rejection reason for user..." required></textarea>
                                    <button type="submit" class="btn btn-secondary" style="font-size:12px;padding:6px 10px;">Reject And Notify</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="muted" style="text-align:center;padding:20px;">No tournaments pending approval.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="panel">
        <div class="header-row">
            <strong>Recent Tournament Reports</strong>
            <span class="muted">Click any card to open full report</span>
        </div>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:14px;">
            @forelse($recentTournamentReports as $tournament)
                <a href="{{ route('admin.tournaments.report', $tournament) }}" style="display:block;border:1px solid var(--line-dim);border-radius:14px;padding:16px;background:var(--card2);transition:border-color .15s;">
                    <div style="display:flex;justify-content:space-between;gap:10px;align-items:flex-start;">
                        <div>
                            <div style="font-size:18px;font-weight:700;color:var(--text);">{{ $tournament->name }}</div>
                            <div class="muted" style="font-size:12px;margin-top:4px;">
                                {{ ucfirst($tournament->creator_type) }}
                                @if($tournament->creator_type === 'user' && $tournament->creator)
                                    · {{ $tournament->creator->username }} ({{ $tournament->creator->user_code }})
                                @endif
                            </div>
                        </div>
                        <span class="badge">{{ ucwords(str_replace('_', ' ', $tournament->status)) }}</span>
                    </div>
                    <div style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px;margin-top:14px;">
                        <div><div class="stat-label">Players</div><div style="font-weight:700;color:var(--text);">{{ $tournament->registrations_count }}/{{ $tournament->max_players }}</div></div>
                        <div><div class="stat-label">Prize Pool</div><div style="font-weight:700;color:var(--gold);">₹{{ number_format((float) $tournament->total_prize_pool, 0) }}</div></div>
                        <div><div class="stat-label">Completed</div><div style="font-weight:700;color:var(--text);">{{ $tournament->completed_matches_count }}</div></div>
                        <div><div class="stat-label">Pending</div><div style="font-weight:700;color:var(--text);">{{ $tournament->pending_matches_count }}</div></div>
                    </div>
                    <div class="muted" style="margin-top:12px;font-size:12px;">
                        Start: <span data-utc-time="{{ $tournament->tournament_start_at?->toIso8601String() }}">{{ $tournament->tournament_start_at?->format('d M Y, h:i A') ?? '—' }}</span>
                    </div>
                    <div style="margin-top:12px;color:var(--gold);font-weight:700;">Open Full Report →</div>
                </a>
            @empty
                <div class="muted">No tournaments yet.</div>
            @endforelse
        </div>
    </div>

    <div class="panel">
        <div class="header-row">
            <div>
                <strong>Tournament Form</strong>
                <div class="muted" style="margin-top:4px;">Open popup, fill tournament details, and submit.</div>
            </div>
            <div style="display:flex;gap:10px;flex-wrap:wrap;">
                <button type="button" class="btn" data-modal-open="adminTournamentModal">{{ $isEdit ? 'Edit Tournament' : 'Create Tournament' }}</button>
                @if ($isEdit)
                    <a class="btn btn-secondary" href="{{ route('admin.tournaments.index') }}">New Tournament</a>
                @endif
            </div>
        </div>
    </div>

    <div id="adminTournamentModal" class="modal-shell {{ ($isEdit || $errors->any()) ? 'is-open' : '' }}">
        <div class="modal-backdrop" data-modal-close="adminTournamentModal"></div>
        <div class="modal-card">
            <div class="modal-head">
                <div>
                    <div style="font-size:20px;font-weight:700;">{{ $isEdit ? 'Edit Tournament' : 'Create Tournament' }}</div>
                    <div class="muted">Fill tournament details and submit.</div>
                    <div class="muted" style="margin-top:4px;">Timezone: <span data-admin-timezone>UTC</span></div>
                </div>
                <button type="button" class="modal-close" data-modal-close="adminTournamentModal">×</button>
            </div>
            <form method="POST" action="{{ $formAction }}" class="stack">
                @csrf
                @if ($isEdit) @method('PUT') @endif
                <input type="hidden" name="timezone" id="admin_tournament_timezone" value="">

                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:14px;">
                    <div><label>Name</label><input name="name" value="{{ old('name', $t?->name) }}" required></div>
                    <div><label>Type</label><select name="type">@foreach (['public','private'] as $v)<option value="{{ $v }}" @selected(old('type', $t?->type ?? 'public') === $v)>{{ ucfirst($v) }}</option>@endforeach</select></div>
                    <div><label>Format</label><select name="format"><option value="knockout" @selected(old('format', $t?->format ?? 'knockout') === 'knockout')>Knockout</option><option value="round_robin" @selected(old('format', $t?->format) === 'round_robin')>Round Robin</option><option value="double_elim" @selected(old('format', $t?->format) === 'double_elim')>Double Elimination</option><option value="group_knockout" @selected(old('format', $t?->format) === 'group_knockout')>Group + Knockout</option></select></div>
                    <div><label>Status</label><select name="status">@foreach (['draft','registration_open','registration_closed','in_progress','completed','cancelled'] as $s)<option value="{{ $s }}" @selected(old('status', $t?->status ?? 'registration_open') === $s)>{{ ucwords(str_replace('_',' ',$s)) }}</option>@endforeach</select></div>
                    <div><label>Entry Fee</label><input type="number" step="0.01" name="entry_fee" value="{{ old('entry_fee', $t?->entry_fee ?? 10) }}" required></div>
                    <div><label>Max Players</label><select name="max_players">@foreach ([4, 8, 16, 32, 64] as $n)<option value="{{ $n }}" @selected((int) old('max_players', $t?->max_players ?? 8) === $n)>{{ $n }}</option>@endforeach</select></div>
                    <div><label>Players Per Match</label><select name="players_per_match"><option value="4" @selected((int) old('players_per_match', $t?->players_per_match ?? 4) === 4)>4 Players</option><option value="2" @selected((int) old('players_per_match', $t?->players_per_match) === 2)>2 Players</option></select></div>
                    <div><label>Platform Fee %</label><input type="number" step="0.1" name="platform_fee_pct" value="{{ old('platform_fee_pct', $t?->platform_fee_pct ?? 20) }}"></div>
                    <div><label>Bracket Mode</label><select name="bracket_mode"><option value="auto" @selected(old('bracket_mode', $t?->bracket_mode ?? 'auto') === 'auto')>Auto</option><option value="manual" @selected(old('bracket_mode', $t?->bracket_mode) === 'manual')>Manual</option></select></div>
                    <div><label>Allow Bots</label><select name="bot_allowed"><option value="0" @selected(! old('bot_allowed', $t?->bot_allowed ?? true))>No</option><option value="1" @selected((bool) old('bot_allowed', $t?->bot_allowed ?? true))>Yes</option></select></div>
                    <div><label>Max Bot %</label><input type="number" step="1" name="max_bot_pct" value="{{ old('max_bot_pct', $t?->max_bot_pct ?? 5) }}" min="0" max="100"></div>
                    <div><label>Bot Start Policy</label><select name="bot_start_policy"><option value="disabled" @selected(old('bot_start_policy', $t?->bot_start_policy ?? 'hybrid') === 'disabled')>Disabled</option><option value="fill_missing" @selected(old('bot_start_policy', $t?->bot_start_policy ?? 'hybrid') === 'fill_missing')>Fill Missing Seats</option><option value="replace_offline" @selected(old('bot_start_policy', $t?->bot_start_policy ?? 'hybrid') === 'replace_offline')>Replace Offline Players</option><option value="hybrid" @selected(old('bot_start_policy', $t?->bot_start_policy ?? 'hybrid') === 'hybrid')>Hybrid</option></select></div>
                    <div><label>Min Real Players To Start</label><input type="number" name="min_real_players_to_start" value="{{ old('min_real_players_to_start', $t?->min_real_players_to_start ?? 1) }}" min="1" max="4"></div>
                    <div><label>Bot Fill Delay (sec)</label><input type="number" name="bot_fill_after_seconds" value="{{ old('bot_fill_after_seconds', $t?->bot_fill_after_seconds ?? 8) }}" min="0" max="300"></div>
                    <div><label>Registration Opens</label><input type="datetime-local" name="registration_start_at" value="{{ old('registration_start_at') }}" data-utc="{{ $t?->registration_start_at?->toIso8601String() }}"></div>
                    <div><label>Registration Closes</label><input type="datetime-local" name="registration_end_at" value="{{ old('registration_end_at') }}" data-utc="{{ $t?->registration_end_at?->toIso8601String() }}"></div>
                    <div><label>Tournament Start</label><input type="datetime-local" name="tournament_start_at" required value="{{ old('tournament_start_at') }}" data-utc="{{ $t?->tournament_start_at?->toIso8601String() }}"></div>
                    <div><label>Private Password</label><input name="invite_password" value="{{ old('invite_password', $t?->invite_password) }}"></div>
                </div>

                <div>
                    <strong>Playing Slots</strong>
                    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:10px;margin-top:10px;">
                        @for ($slot = 1; $slot <= 5; $slot++)
                            @php
                                $slotStart = data_get($t?->play_slots, ($slot - 1).'.start_at');
                                $slotEnd = data_get($t?->play_slots, ($slot - 1).'.end_at');
                            @endphp
                            <div class="panel" style="padding:12px;">
                                <div style="font-size:14px;font-weight:700;margin-bottom:8px;">Slot {{ $slot }}</div>
                                <label>Start</label>
                                <input type="datetime-local" name="play_slot_start_{{ $slot }}" value="{{ old('play_slot_start_'.$slot) }}" data-utc="{{ $slotStart ? \Illuminate\Support\Carbon::parse($slotStart)->toIso8601String() : '' }}">
                                <label style="margin-top:8px;">End</label>
                                <input type="datetime-local" name="play_slot_end_{{ $slot }}" value="{{ old('play_slot_end_'.$slot) }}" data-utc="{{ $slotEnd ? \Illuminate\Support\Carbon::parse($slotEnd)->toIso8601String() : '' }}">
                            </div>
                        @endfor
                    </div>
                </div>

                <div><label>Terms & Conditions</label><textarea name="terms_conditions" rows="2">{{ old('terms_conditions', $t?->terms_conditions) }}</textarea></div>

                <div>
                    <strong>Prize Distribution</strong>
                    <div style="display:grid;grid-template-columns:repeat(5,1fr);gap:10px;margin-top:10px;">
                        @for ($pos = 1; $pos <= 5; $pos++)
                            <div>
                                <label>{{ ['1st','2nd','3rd','4th','5th'][$pos-1] }}</label>
                                <input type="number" step="0.1" name="prize_pct_{{ $pos }}" value="{{ old("prize_pct_{$pos}", $prizePct($pos)) }}" min="0" max="100">
                            </div>
                        @endfor
                    </div>
                </div>

                <div style="display:flex;gap:10px;flex-wrap:wrap;">
                    <button class="btn" type="submit">{{ $isEdit ? 'Update Tournament' : 'Create Tournament' }}</button>
                    <button type="button" class="btn btn-secondary" data-modal-close="adminTournamentModal">Close</button>
                    @if($isEdit)
                        <a href="{{ route('admin.tournaments.index') }}" class="btn btn-secondary">Cancel Edit</a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    @php
        $renderRow = function (App\Models\Tournament $tournament) {
            $isDraft = $tournament->status === 'draft';
            $isOpen = $tournament->status === 'registration_open';
            $fake = (int) ($tournament->fake_registrations_count ?? 0);
            return compact('isDraft', 'isOpen', 'fake');
        };
    @endphp

    <div class="panel">
        <div class="header-row">
            <strong>User-Created Tournaments</strong>
            <span class="muted">{{ $userTournaments->count() }} total</span>
        </div>
        <div class="table-wrap responsive-table">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Tournament</th>
                        <th>Creator</th>
                        <th>Status</th>
                        <th>Players</th>
                        <th>Matches</th>
                        <th>Prize Pool</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($userTournaments as $tournament)
                        @php extract($renderRow($tournament)); @endphp
                        <tr>
                            <td data-label="ID">{{ $tournament->id }}</td>
                            <td data-label="Tournament">
                                <strong>{{ $tournament->name }}</strong>
                                <div class="muted" style="font-size:12px;">{{ ucfirst($tournament->type) }} · {{ ucwords(str_replace('_',' ',$tournament->format)) }} · <span data-utc-time="{{ $tournament->created_at?->toIso8601String() }}">{{ $tournament->created_at?->format('d M Y, h:i A') }}</span></div>
                            </td>
                            <td data-label="Creator">{{ $tournament->creator?->username ?? 'User' }}<div class="muted" style="font-size:12px;">{{ $tournament->creator?->user_code ?? '—' }}</div></td>
                            <td data-label="Status">{{ ucwords(str_replace('_',' ',$tournament->status)) }}</td>
                            <td data-label="Players">{{ $tournament->registrations_count + $fake }}/{{ $tournament->max_players }}</td>
                            <td data-label="Matches">{{ $tournament->completed_matches_count }} complete · {{ $tournament->pending_matches_count }} pending</td>
                            <td data-label="Prize Pool">₹{{ number_format((float) $tournament->total_prize_pool, 2) }}</td>
                            <td data-label="Actions" style="white-space:nowrap;">
                                <a class="btn" style="font-size:12px;padding:4px 10px;margin-right:4px;" href="{{ route('admin.tournaments.report', $tournament) }}">Open Report</a>
                                @if(!$tournament->is_approved)
                                    <form method="POST" action="{{ route('admin.tournaments.approve', $tournament) }}" style="display:inline;">@csrf<button type="submit" class="btn" style="background:linear-gradient(135deg,var(--green),#028A5E);color:#000;font-size:12px;padding:4px 10px;margin-right:4px;">Approve</button></form>
                                @endif
                                @if($isDraft)
                                    <form method="POST" action="{{ route('admin.tournaments.force-live', $tournament) }}" style="display:inline;">@csrf<button type="submit" class="btn" style="background:linear-gradient(135deg,#FF9500,#d97706);color:#000;font-size:12px;padding:4px 10px;margin-right:4px;">Force Live</button></form>
                                @endif
                                <a class="btn btn-secondary" style="font-size:12px;padding:4px 10px;" href="{{ route('admin.tournaments.edit', $tournament) }}">Edit</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="muted" style="text-align:center;padding:20px;">No user-created tournaments yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="panel">
        <div class="header-row">
            <strong>Admin-Created Tournaments</strong>
            <span class="muted">{{ $adminTournaments->count() }} total</span>
        </div>
        <div class="table-wrap responsive-table">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Tournament</th>
                        <th>Status</th>
                        <th>Players</th>
                        <th>Matches</th>
                        <th>Prize Pool</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($adminTournaments as $tournament)
                        @php extract($renderRow($tournament)); @endphp
                        <tr>
                            <td data-label="ID">{{ $tournament->id }}</td>
                            <td data-label="Tournament">
                                <strong>{{ $tournament->name }}</strong>
                                <div class="muted" style="font-size:12px;">{{ ucfirst($tournament->type) }} · {{ ucwords(str_replace('_',' ',$tournament->format)) }} · <span data-utc-time="{{ $tournament->created_at?->toIso8601String() }}">{{ $tournament->created_at?->format('d M Y, h:i A') }}</span></div>
                            </td>
                            <td data-label="Status">{{ ucwords(str_replace('_',' ',$tournament->status)) }}</td>
                            <td data-label="Players">{{ $tournament->registrations_count + $fake }}/{{ $tournament->max_players }}</td>
                            <td data-label="Matches">{{ $tournament->completed_matches_count }} complete · {{ $tournament->pending_matches_count }} pending</td>
                            <td data-label="Prize Pool">₹{{ number_format((float) $tournament->total_prize_pool, 2) }}</td>
                            <td data-label="Actions" style="white-space:nowrap;">
                                <a class="btn" style="font-size:12px;padding:4px 10px;margin-right:4px;" href="{{ route('admin.tournaments.report', $tournament) }}">Open Report</a>
                                @if($isDraft)
                                    <form method="POST" action="{{ route('admin.tournaments.force-live', $tournament) }}" style="display:inline;">@csrf<button type="submit" class="btn" style="background:linear-gradient(135deg,#FF9500,#d97706);color:#000;font-size:12px;padding:4px 10px;margin-right:4px;">Force Live</button></form>
                                @endif
                                <a class="btn btn-secondary" style="font-size:12px;padding:4px 10px;" href="{{ route('admin.tournaments.edit', $tournament) }}">Edit</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="muted" style="text-align:center;padding:20px;">No admin-created tournaments yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
const adminTournamentTimezone = (Intl.DateTimeFormat().resolvedOptions().timeZone || 'UTC');
const adminTimezoneLabel = document.querySelector('[data-admin-timezone]');
const adminTimezoneInput = document.getElementById('admin_tournament_timezone');
if (adminTimezoneLabel) adminTimezoneLabel.textContent = adminTournamentTimezone;
if (adminTimezoneInput) adminTimezoneInput.value = adminTournamentTimezone;

const toLocalInputValueAdmin = (isoString) => {
    if (!isoString) return '';
    const date = new Date(isoString);
    if (Number.isNaN(date.getTime())) return '';
    const pad = (n) => String(n).padStart(2, '0');
    return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`;
};

document.querySelectorAll('input[type="datetime-local"][data-utc]').forEach((input) => {
    if (!input.value && input.dataset.utc) {
        input.value = toLocalInputValueAdmin(input.dataset.utc);
    }
});

document.querySelectorAll('[data-utc-time]').forEach((node) => {
    const iso = node.getAttribute('data-utc-time');
    if (!iso) return;
    const date = new Date(iso);
    if (Number.isNaN(date.getTime())) return;
    node.textContent = new Intl.DateTimeFormat(undefined, {
        year: 'numeric',
        month: 'short',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
        hour12: true,
        timeZone: adminTournamentTimezone,
        timeZoneName: 'short',
    }).format(date);
});

document.querySelectorAll('[data-modal-open]').forEach(function (button) {
    button.addEventListener('click', function () {
        document.getElementById(button.getAttribute('data-modal-open'))?.classList.add('is-open');
    });
});
document.querySelectorAll('[data-modal-close]').forEach(function (button) {
    button.addEventListener('click', function () {
        document.getElementById(button.getAttribute('data-modal-close'))?.classList.remove('is-open');
    });
});
</script>
@endpush
