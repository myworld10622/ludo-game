@extends('admin.layouts.app')

@section('title', $tournament->name . ' Report')
@section('heading', 'Tournament Report')
@section('subheading', $tournament->name . ' · Full admin report')

@section('content')
<div class="stack">
    <div>
        <a href="{{ route('admin.tournaments.index') }}" style="color:#2563eb;font-size:14px;">← Back to Tournaments</a>
    </div>

    <div class="panel" style="background:linear-gradient(135deg,#0f172a,#153e75);color:#fff;border:none;">
        <div class="badge" style="background:rgba(255,255,255,0.14);color:#fff;">Admin Report</div>
        <h2 style="margin:12px 0 6px;font-size:30px;">{{ $tournament->name }}</h2>
        <div style="color:rgba(255,255,255,0.86);line-height:1.7;">
            Owner:
            {{ $tournament->creator_type === 'user' ? ($tournament->creator?->username . ' (' . ($tournament->creator?->user_code ?? '—') . ')') : 'Admin' }}
            · Created <span data-utc-time="{{ $tournament->created_at?->toIso8601String() }}">{{ $tournament->created_at?->format('d M Y, h:i A') ?? '—' }}</span>
            · Status {{ ucwords(str_replace('_', ' ', $tournament->status)) }}
        </div>
        <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:14px;">
            <a href="{{ route('admin.tournaments.export', $tournament) }}" class="btn btn-secondary">Download Excel</a>
            <a href="{{ route('admin.tournaments.print', ['tournament' => $tournament, 'mode' => 'pdf']) }}" class="btn btn-secondary" target="_blank">Download PDF</a>
            <a href="{{ route('admin.tournaments.print', $tournament) }}" class="btn btn-secondary" target="_blank">Print Report</a>
            <a href="{{ route('admin.tournaments.edit', $tournament) }}" class="btn btn-secondary">Edit Tournament</a>
            @if(!$tournament->is_approved)
                <form method="POST" action="{{ route('admin.tournaments.approve', $tournament) }}">@csrf<button type="submit" class="btn">Approve</button></form>
            @endif
        </div>
    </div>

    @if(!$tournament->is_approved)
        <div class="panel">
            <div style="font-size:16px;font-weight:700;margin-bottom:10px;">Reject With Reason</div>
            <form method="POST" action="{{ route('admin.tournaments.reject', $tournament) }}" style="display:grid;gap:10px;">
                @csrf
                <textarea name="reason" rows="3" placeholder="Write rejection reason for user..." required></textarea>
                <div>
                    <button type="submit" class="btn btn-secondary">Reject And Notify User</button>
                </div>
            </form>
        </div>
    @endif

    <div class="stats-grid" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:14px;">
        <div class="panel"><div class="stat-label">Registrations</div><div style="font-size:28px;font-weight:700;">{{ $stats['total_players'] }}</div></div>
        <div class="panel"><div class="stat-label">Completed Matches</div><div style="font-size:28px;font-weight:700;">{{ $stats['completed_matches'] }}</div></div>
        <div class="panel"><div class="stat-label">Pending Matches</div><div style="font-size:28px;font-weight:700;">{{ $stats['pending_matches'] }}</div></div>
        <div class="panel"><div class="stat-label">Cancelled Matches</div><div style="font-size:28px;font-weight:700;">{{ $stats['cancelled_matches'] }}</div></div>
        <div class="panel"><div class="stat-label">Prize Pool</div><div style="font-size:28px;font-weight:700;">₹{{ number_format((float) $tournament->total_prize_pool, 0) }}</div></div>
        <div class="panel"><div class="stat-label">Platform Fee</div><div style="font-size:28px;font-weight:700;">₹{{ number_format((float) $tournament->platform_fee_amount, 0) }}</div></div>
    </div>

    <div class="panel">
        <div style="font-size:18px;font-weight:700;margin-bottom:12px;">Tournament Submission Details</div>
        <div style="display:flex;justify-content:flex-end;margin-bottom:12px;">
            <a href="{{ route('admin.tournaments.edit', $tournament) }}" class="btn">Open In Edit Form</a>
        </div>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:12px;">
            <div class="panel"><div class="stat-label">Tournament Name</div><div>{{ $tournament->name }}</div></div>
            <div class="panel"><div class="stat-label">Type</div><div>{{ ucfirst($tournament->type) }}</div></div>
            <div class="panel"><div class="stat-label">Format</div><div>{{ ucwords(str_replace('_', ' ', $tournament->format)) }}</div></div>
            <div class="panel"><div class="stat-label">Bracket Mode</div><div>{{ ucfirst($tournament->bracket_mode ?? 'auto') }}</div></div>
            <div class="panel"><div class="stat-label">Entry Fee</div><div>₹{{ number_format((float) $tournament->entry_fee, 2) }}</div></div>
            <div class="panel"><div class="stat-label">Max Players</div><div>{{ $tournament->max_players }}</div></div>
            <div class="panel"><div class="stat-label">Players Per Match</div><div>{{ $tournament->players_per_match }}</div></div>
            <div class="panel"><div class="stat-label">Platform Fee %</div><div>{{ number_format((float) $tournament->platform_fee_pct, 2) }}%</div></div>
            <div class="panel"><div class="stat-label">Bots Allowed</div><div>{{ $tournament->bot_allowed ? 'Yes' : 'No' }}</div></div>
            <div class="panel"><div class="stat-label">Max Bot %</div><div>{{ number_format((float) $tournament->max_bot_pct, 2) }}%</div></div>
            <div class="panel"><div class="stat-label">Bot Start Policy</div><div>{{ ucwords(str_replace('_', ' ', $tournament->resolveBotStartPolicy())) }}</div></div>
            <div class="panel"><div class="stat-label">Min Real Players</div><div>{{ $tournament->resolveMinRealPlayersToStart() }}</div></div>
            <div class="panel"><div class="stat-label">Bot Fill Delay</div><div>{{ $tournament->resolveBotFillAfterSeconds() }} sec</div></div>
            <div class="panel"><div class="stat-label">Registration Start</div><div><span data-utc-time="{{ $tournament->registration_start_at?->toIso8601String() }}">{{ $tournament->registration_start_at?->format('d M Y, h:i A') ?? '—' }}</span></div></div>
            <div class="panel"><div class="stat-label">Registration End</div><div><span data-utc-time="{{ $tournament->registration_end_at?->toIso8601String() }}">{{ $tournament->registration_end_at?->format('d M Y, h:i A') ?? '—' }}</span></div></div>
            <div class="panel"><div class="stat-label">Tournament Start</div><div><span data-utc-time="{{ $tournament->tournament_start_at?->toIso8601String() }}">{{ $tournament->tournament_start_at?->format('d M Y, h:i A') ?? '—' }}</span></div></div>
            <div class="panel"><div class="stat-label">Invite Password</div><div>{{ $tournament->invite_password ?: '—' }}</div></div>
            <div class="panel"><div class="stat-label">Invite Code</div><div>{{ $tournament->invite_code ?: '—' }}</div></div>
            <div class="panel"><div class="stat-label">Approval State</div><div>{{ $tournament->is_approved ? 'Approved' : 'Pending Review' }}</div></div>
        </div>
        @if(!empty($tournament->play_slots))
            <div style="margin-top:12px;">
                <div style="font-size:16px;font-weight:700;margin-bottom:10px;">Playing Slots</div>
                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px;">
                    @foreach($tournament->play_slots as $slot)
                        <div class="panel">
                            <div class="stat-label">{{ $slot['label'] ?? 'Slot' }}</div>
                            <div><span data-utc-time="{{ \Illuminate\Support\Carbon::parse($slot['start_at'])->toIso8601String() }}">{{ \Illuminate\Support\Carbon::parse($slot['start_at'])->format('d M Y, h:i A') }}</span></div>
                            <div class="muted" style="margin-top:4px;">to <span data-utc-time="{{ \Illuminate\Support\Carbon::parse($slot['end_at'])->toIso8601String() }}">{{ \Illuminate\Support\Carbon::parse($slot['end_at'])->format('d M Y, h:i A') }}</span></div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-top:12px;">
            <div class="panel">
                <div class="stat-label">Description</div>
                <div style="white-space:pre-wrap;line-height:1.7;">{{ $tournament->description ?: '—' }}</div>
            </div>
            <div class="panel">
                <div class="stat-label">Terms & Conditions</div>
                <div style="white-space:pre-wrap;line-height:1.7;">{{ $tournament->terms_conditions ?: '—' }}</div>
            </div>
        </div>

        <div style="margin-top:12px;">
            <div style="font-size:16px;font-weight:700;margin-bottom:10px;">Prize Split Submitted By User</div>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Position</th><th>Prize %</th><th>Prize Amount</th><th>Payout Status</th></tr></thead>
                    <tbody>
                    @forelse($prizes as $prize)
                        <tr>
                            <td>#{{ $prize->position }}</td>
                            <td>{{ number_format((float) $prize->prize_pct, 2) }}%</td>
                            <td>₹{{ number_format((float) $prize->prize_amount, 2) }}</td>
                            <td>{{ ucfirst($prize->payout_status) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="muted">No prize rows found.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="panel">
        <div style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:12px;">
            <a href="#summary" class="btn">Summary</a>
            <a href="#winners" class="btn btn-secondary">Winners</a>
            <a href="#registrations" class="btn btn-secondary">Registrations</a>
            <a href="#matches" class="btn btn-secondary">Matches</a>
            <a href="#financials" class="btn btn-secondary">Financials</a>
        </div>

        <div id="summary" style="margin-bottom:24px;">
            <div style="font-size:16px;font-weight:700;margin-bottom:10px;">Summary</div>
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(190px,1fr));gap:12px;">
                <div class="panel"><div class="stat-label">Type</div><div>{{ ucfirst($tournament->type) }}</div></div>
                <div class="panel"><div class="stat-label">Format</div><div>{{ ucwords(str_replace('_', ' ', $tournament->format)) }}</div></div>
                <div class="panel"><div class="stat-label">Entry Fee</div><div>₹{{ number_format((float) $tournament->entry_fee, 2) }}</div></div>
                <div class="panel"><div class="stat-label">Players / Match</div><div>{{ $tournament->players_per_match }}</div></div>
                <div class="panel"><div class="stat-label">Gross Entry</div><div>₹{{ number_format((float) $stats['gross_entry'], 2) }}</div></div>
                <div class="panel"><div class="stat-label">Override Matches</div><div>{{ $stats['override_matches'] }}</div></div>
            </div>
        </div>

        <div id="winners" style="margin-bottom:24px;">
            <div style="font-size:16px;font-weight:700;margin-bottom:10px;">Winners</div>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Position</th><th>Winner</th><th>User ID</th><th>Prize</th><th>Status</th></tr></thead>
                    <tbody>
                    @forelse($prizes as $prize)
                        <tr>
                            <td>#{{ $prize->position }}</td>
                            <td>{{ $prize->winner?->username ?? 'Pending' }}</td>
                            <td>{{ $prize->winner?->user_code ?? '—' }}</td>
                            <td>₹{{ number_format((float) $prize->prize_amount, 2) }}</td>
                            <td>{{ ucfirst($prize->payout_status) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="muted">No winner rows found.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div id="registrations" style="margin-bottom:24px;">
            <div style="font-size:16px;font-weight:700;margin-bottom:10px;">Registrations</div>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Player</th><th>User ID</th><th>Status</th><th>Position</th><th>Prize</th><th>Registered</th></tr></thead>
                    <tbody>
                    @forelse($registrations as $registration)
                        <tr>
                            <td>{{ $registration->displayName() }}</td>
                            <td>{{ $registration->user?->user_code ?? 'Bot' }}</td>
                            <td>{{ ucwords(str_replace('_', ' ', $registration->status)) }}</td>
                            <td>{{ $registration->final_position ? '#' . $registration->final_position : '—' }}</td>
                            <td>{{ (float) $registration->prize_won > 0 ? '₹' . number_format((float) $registration->prize_won, 2) : '—' }}</td>
                            <td><span data-utc-time="{{ $registration->registered_at?->toIso8601String() }}">{{ $registration->registered_at?->format('d M Y, h:i A') ?? '—' }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="muted">No registrations found.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div id="matches" style="margin-bottom:24px;">
            <div style="font-size:16px;font-weight:700;margin-bottom:10px;">Round-wise Matches</div>
            @forelse($rounds as $round)
                <div class="panel" style="margin-bottom:14px;">
                    <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;margin-bottom:10px;">
                        <div>
                            <strong>Round {{ $round['round_number'] }}</strong>
                            <div class="muted" style="font-size:12px;">{{ $round['completed_matches'] }} completed · {{ $round['pending_matches'] }} pending · {{ $round['cancelled_matches'] }} cancelled</div>
                        </div>
                        <span class="badge">{{ $round['total_matches'] }} matches</span>
                    </div>
                    <div class="table-wrap">
                        <table>
                            <thead><tr><th>Match</th><th>Status</th><th>Players</th><th>Winner</th><th>Override</th></tr></thead>
                            <tbody>
                            @foreach($round['matches'] as $match)
                                <tr>
                                    <td>#{{ $match->match_number }}</td>
                                    <td>{{ ucwords(str_replace('_', ' ', $match->status)) }}</td>
                                    <td>{{ $match->players->isNotEmpty() ? $match->players->map(fn ($player) => $player->registration?->displayName() ?? 'Unknown')->join(', ') : 'No players' }}</td>
                                    <td>{{ $match->winner?->displayName() ?? $match->forcedWinner?->displayName() ?? 'Pending' }}</td>
                                    <td>{{ $match->is_admin_override ? ($match->admin_override_note ?: 'Yes') : 'No' }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @empty
                <div class="muted">No rounds found.</div>
            @endforelse
        </div>

        <div id="financials">
            <div style="font-size:16px;font-weight:700;margin-bottom:10px;">Financials</div>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Time</th><th>User</th><th>Type</th><th>Amount</th><th>Description</th></tr></thead>
                    <tbody>
                    @forelse($financialRows as $row)
                        <tr>
                            <td><span data-utc-time="{{ $row->created_at?->toIso8601String() }}">{{ $row->created_at?->format('d M Y, h:i A') ?? '—' }}</span></td>
                            <td>{{ $row->user?->username ?? 'System' }}</td>
                            <td>{{ ucwords(str_replace('_', ' ', $row->type ?? 'transaction')) }}</td>
                            <td>₹{{ number_format((float) ($row->amount ?? 0), 2) }}</td>
                            <td>{{ $row->description ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="muted">No wallet transaction rows found.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
const adminReportTimezone = (Intl.DateTimeFormat().resolvedOptions().timeZone || 'UTC');
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
        timeZone: adminReportTimezone,
        timeZoneName: 'short',
    }).format(date);
});
</script>
@endpush
