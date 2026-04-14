@extends('user.layouts.app')

@section('title', 'My Tournaments')
@section('heading', 'My Tournaments')
@section('subheading', 'Create, review, and manage only your own tournaments')

@section('content')
@php($editing = $editingTournament ?? null)

<div class="panel page-hero" style="margin-bottom:24px;">
    <div>
        <div class="eyebrow">Tournament Workspace</div>
        <h2 style="margin:8px 0 10px;font-size:30px;">Your tournaments, reports, winners, and financials in one place</h2>
        <p class="muted" style="max-width:780px;line-height:1.7;margin:0;">
            Create tournament, monitor progress, open detailed reports, and drill into registrations, payouts, and match outcomes.
        </p>
    </div>
    <div class="hero-stats">
        <div class="hero-chip">
            <strong>{{ $tournaments->count() }}</strong>
            <span>Total Tournaments</span>
        </div>
        <div class="hero-chip">
            <strong>{{ $tournaments->sum('running_matches_count') }}</strong>
            <span>Running Matches</span>
        </div>
        <div class="hero-chip">
            <strong>{{ $tournaments->sum('completed_matches_count') }}</strong>
            <span>Completed Matches</span>
        </div>
    </div>
</div>

<div class="panel" style="margin-bottom:24px;display:flex;justify-content:space-between;gap:12px;align-items:center;flex-wrap:wrap;">
    <div>
        <div style="font-size:18px;font-weight:700;">Tournament Form</div>
        <div class="muted">Open popup, fill tournament information, and submit.</div>
    </div>
    <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;">
        <a href="{{ route('tournament.guide') }}" target="_blank"
           style="display:inline-flex;align-items:center;gap:6px;padding:10px 14px;border-radius:12px;border:1px solid var(--line);background:linear-gradient(135deg,#fff5ea 0%,#f8e3cf 100%);color:var(--brand-dark);font-weight:700;font-size:14px;">
            📖 Learn &amp; Guide
        </a>
        <button type="button" class="btn" data-modal-open="userTournamentModal">{{ $editing ? 'Edit Tournament' : 'Create Tournament' }}</button>
    </div>
</div>

<div id="userTournamentModal" class="modal-shell {{ ($editing || $errors->any()) ? 'is-open' : '' }}">
    <div class="modal-backdrop" data-modal-close="userTournamentModal"></div>
    <div class="modal-card">
        <div class="modal-head">
            <div>
                <div style="font-size:20px;font-weight:700;">{{ $editing ? 'Edit Tournament' : 'Create Tournament' }}</div>
                <div class="muted">Fill tournament information and submit.</div>
                <div class="muted" style="margin-top:4px;">Timezone: <span data-user-timezone>UTC</span></div>
            </div>
            <button type="button" class="modal-close" data-modal-close="userTournamentModal">×</button>
        </div>
        <form method="POST" action="{{ $editing ? route('panel.tournaments.update', $editing) : route('panel.tournaments.store') }}">
            @csrf
            @if($editing)
                @method('PUT')
            @endif
            <input type="hidden" name="timezone" id="user_tournament_timezone" value="">
            <div class="form-grid">
                <div><label>Name</label><input name="name" value="{{ old('name', $editing?->name) }}" required></div>
                <div><label>Type</label><select name="type">@foreach(['public','private'] as $type)<option value="{{ $type }}" {{ old('type', $editing?->type ?? 'public') === $type ? 'selected' : '' }}>{{ ucfirst($type) }}</option>@endforeach</select></div>
                <div><label>Format</label><select name="format">@foreach(['knockout','round_robin','double_elim','group_knockout'] as $format)<option value="{{ $format }}" {{ old('format', $editing?->format ?? 'knockout') === $format ? 'selected' : '' }}>{{ ucwords(str_replace('_', ' ', $format)) }}</option>@endforeach</select></div>
                <div><label>Entry Fee</label><input type="number" step="0.01" name="entry_fee" value="{{ old('entry_fee', $editing?->entry_fee ?? 0) }}" required></div>
                <div><label>Max Players</label><select name="max_players">@foreach([4,8,16,32,64,112] as $max)<option value="{{ $max }}" {{ (int) old('max_players', $editing?->max_players ?? 4) === $max ? 'selected' : '' }}>{{ $max }}</option>@endforeach</select></div>
                <div><label>Players Per Match</label><select name="players_per_match">@foreach([2,4] as $ppm)<option value="{{ $ppm }}" {{ (int) old('players_per_match', $editing?->players_per_match ?? 4) === $ppm ? 'selected' : '' }}>{{ $ppm }}</option>@endforeach</select></div>
                <div><label>Platform Fee %</label><input type="number" step="0.01" name="platform_fee_pct" value="{{ old('platform_fee_pct', $editing?->platform_fee_pct ?? 20) }}"></div>
                <div><label>Registration Start</label><input type="datetime-local" name="registration_start_at" value="{{ old('registration_start_at') }}" data-utc="{{ $editing?->registration_start_at?->toIso8601String() }}"></div>
                <div><label>Registration End</label><input type="datetime-local" name="registration_end_at" value="{{ old('registration_end_at') }}" data-utc="{{ $editing?->registration_end_at?->toIso8601String() }}"></div>
                <div><label>Tournament Start</label><input type="datetime-local" name="tournament_start_at" value="{{ old('tournament_start_at') }}" data-utc="{{ $editing?->tournament_start_at?->toIso8601String() }}" required></div>
                <div><label>Bracket Mode</label><select name="bracket_mode">@foreach(['auto','manual'] as $mode)<option value="{{ $mode }}" {{ old('bracket_mode', $editing?->bracket_mode ?? 'auto') === $mode ? 'selected' : '' }}>{{ ucfirst($mode) }}</option>@endforeach</select></div>
            </div>
            <div class="stack-compact" style="margin-top:14px;">
                <div style="font-size:15px;font-weight:700;">Playing Slots</div>
                <div class="form-grid">
                    @for($slot = 1; $slot <= 5; $slot++)
                        <div class="panel" style="padding:12px;">
                            <div style="font-size:14px;font-weight:700;margin-bottom:10px;">Slot {{ $slot }}</div>
                            <div><label>Start</label><input type="datetime-local" name="play_slot_start_{{ $slot }}" value="{{ old('play_slot_start_'.$slot) }}" data-utc="{{ $editing && data_get($editing->play_slots, ($slot - 1).'.start_at') ? \Illuminate\Support\Carbon::parse(data_get($editing->play_slots, ($slot - 1).'.start_at'))->toIso8601String() : '' }}"></div>
                            <div style="margin-top:10px;"><label>End</label><input type="datetime-local" name="play_slot_end_{{ $slot }}" value="{{ old('play_slot_end_'.$slot) }}" data-utc="{{ $editing && data_get($editing->play_slots, ($slot - 1).'.end_at') ? \Illuminate\Support\Carbon::parse(data_get($editing->play_slots, ($slot - 1).'.end_at'))->toIso8601String() : '' }}"></div>
                        </div>
                    @endfor
                </div>
            </div>
            <div class="stack-compact">
                <div><label>Description</label><textarea name="description">{{ old('description', $editing?->description) }}</textarea></div>
                <div><label>Terms & Conditions</label><textarea name="terms_conditions">{{ old('terms_conditions', $editing?->terms_conditions) }}</textarea></div>
                <div class="prize-grid">
                    @for($pos = 1; $pos <= 5; $pos++)
                        <div>
                            <label>Prize % {{ $pos }}</label>
                            <input type="number" step="0.01" name="prize_pct_{{ $pos }}" value="{{ old('prize_pct_'.$pos, $editing?->prizes->firstWhere('position', $pos)?->prize_pct) }}">
                        </div>
                    @endfor
                </div>
            </div>
            <div class="mobile-actions" style="margin-top:16px;">
                <button type="submit" class="btn">{{ $editing ? 'Update Tournament' : 'Create Tournament' }}</button>
                <button type="button" class="btn btn-secondary" data-modal-close="userTournamentModal">Close</button>
                @if($editing)
                    <a href="{{ route('panel.tournaments.index') }}" class="btn btn-secondary">Cancel Edit</a>
                @endif
            </div>
        </form>
    </div>
</div>

<div class="cards-grid" style="margin-bottom:22px;">
    @forelse($tournaments as $tournament)
        <div class="panel tournament-card">
            <div class="card-head">
                <div>
                    <div class="card-title">{{ $tournament->name }}</div>
                    <div class="muted" style="font-size:13px;">
                        Created <span data-utc-time="{{ $tournament->created_at?->toIso8601String() }}">{{ $tournament->created_at?->format('d M Y, h:i A') ?? '—' }}</span>
                    </div>
                </div>
                <div class="stack-compact">
                    <span class="badge">{{ ucwords(str_replace('_', ' ', $tournament->status)) }}</span>
                    <span class="badge {{ $tournament->is_approved ? '' : 'badge-warn' }}">{{ $tournament->is_approved ? 'Approved' : 'Pending Approval' }}</span>
                </div>
            </div>

            <div class="metrics-grid">
                <div><span>Format</span><strong>{{ ucwords(str_replace('_', ' ', $tournament->format)) }}</strong></div>
                <div><span>Players</span><strong>{{ $tournament->current_players }}/{{ $tournament->max_players }}</strong></div>
                <div><span>Real Registrations</span><strong>{{ $tournament->real_registrations_count }}</strong></div>
                <div><span>Winners Marked</span><strong>{{ $tournament->winner_registrations_count }}</strong></div>
                <div><span>Running Matches</span><strong>{{ $tournament->running_matches_count }}</strong></div>
                <div><span>Completed Matches</span><strong>{{ $tournament->completed_matches_count }}</strong></div>
                <div><span>Start Time</span><strong><span data-utc-time="{{ $tournament->tournament_start_at?->toIso8601String() }}">{{ $tournament->tournament_start_at?->format('d M Y, h:i A') ?? '—' }}</span></strong></div>
            </div>

            <div class="card-actions">
                <a href="{{ route('panel.tournaments.report', $tournament) }}" class="btn">Open Report</a>
                <a href="{{ route('panel.tournaments.edit', $tournament) }}" class="btn btn-secondary">Edit</a>
            </div>
        </div>
    @empty
        <div class="panel">
            <div style="font-size:18px;font-weight:700;margin-bottom:6px;">No tournaments created yet</div>
            <div class="muted">Use the form above to create your first tournament and open detailed reporting from here.</div>
        </div>
    @endforelse
</div>

<div class="panel" style="padding:0;overflow:hidden;">
    <div class="table-wrap responsive-table">
        <table>
            <thead>
            <tr>
                <th>Tournament</th>
                <th>Created</th>
                <th>Status</th>
                <th>Players</th>
                <th>Matches</th>
                <th>Financials</th>
                <th>Reports</th>
            </tr>
            </thead>
            <tbody>
            @forelse($tournaments as $tournament)
                <tr>
                    <td data-label="Tournament">
                        <strong>{{ $tournament->name }}</strong>
                        <div class="muted" style="font-size:12px;">{{ ucfirst($tournament->type) }} · {{ ucwords(str_replace('_', ' ', $tournament->format)) }}</div>
                    </td>
                    <td data-label="Created"><span data-utc-time="{{ $tournament->created_at?->toIso8601String() }}">{{ $tournament->created_at?->format('d M Y, h:i A') ?? '—' }}</span></td>
                    <td data-label="Status">{{ ucwords(str_replace('_', ' ', $tournament->status)) }}</td>
                    <td data-label="Players">{{ $tournament->current_players }}/{{ $tournament->max_players }}</td>
                    <td data-label="Matches">{{ $tournament->completed_matches_count }} done · {{ $tournament->running_matches_count }} running</td>
                    <td data-label="Financials">₹{{ number_format((float) $tournament->total_prize_pool, 2) }} pool</td>
                    <td data-label="Reports"><a href="{{ route('panel.tournaments.report', $tournament) }}" class="text-link">View Full Report</a></td>
                </tr>
            @empty
                <tr><td colspan="7" class="muted">No tournament rows yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

@push('scripts')
<script>
const userTournamentTimezone = (Intl.DateTimeFormat().resolvedOptions().timeZone || 'UTC');
const userTimezoneLabel = document.querySelector('[data-user-timezone]');
const userTimezoneInput = document.getElementById('user_tournament_timezone');
if (userTimezoneLabel) userTimezoneLabel.textContent = userTournamentTimezone;
if (userTimezoneInput) userTimezoneInput.value = userTournamentTimezone;

const toLocalInputValue = (isoString) => {
    if (!isoString) return '';
    const date = new Date(isoString);
    if (Number.isNaN(date.getTime())) return '';
    const pad = (n) => String(n).padStart(2, '0');
    return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`;
};

document.querySelectorAll('input[type="datetime-local"][data-utc]').forEach((input) => {
    if (!input.value && input.dataset.utc) {
        input.value = toLocalInputValue(input.dataset.utc);
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
        timeZone: userTournamentTimezone,
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
