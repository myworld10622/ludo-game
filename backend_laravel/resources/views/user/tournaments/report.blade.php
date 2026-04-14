@extends('user.layouts.app')

@section('title', $tournament->name . ' Report')
@section('heading', 'Tournament Report')
@section('subheading', $tournament->name . ' · Full report, winners, registrations, and financials')

@section('content')
<div class="panel page-hero" style="margin-bottom:24px;">
    <div>
        <div class="eyebrow">Report Overview</div>
        <h2 style="margin:8px 0 10px;font-size:30px;">{{ $tournament->name }}</h2>
        <p class="muted" style="line-height:1.7;margin:0;max-width:860px;">
            Created <span data-utc-time="{{ $tournament->created_at?->toIso8601String() }}">{{ $tournament->created_at?->format('d M Y, h:i A') ?? '—' }}</span> ·
            Status {{ ucwords(str_replace('_', ' ', $tournament->status)) }} ·
            {{ ucfirst($tournament->type) }} ·
            {{ ucwords(str_replace('_', ' ', $tournament->format)) }}
        </p>
    </div>
    <div style="display:flex;gap:10px;flex-wrap:wrap;">
        <a href="{{ route('panel.tournaments.export', $tournament) }}" class="btn btn-secondary">Download Excel</a>
        <a href="{{ route('panel.tournaments.print', ['tournament' => $tournament, 'mode' => 'pdf']) }}" class="btn btn-secondary" target="_blank">Download PDF</a>
        <a href="{{ route('panel.tournaments.print', $tournament) }}" class="btn btn-secondary" target="_blank">Print Report</a>
        <a href="{{ route('panel.tournaments.index') }}" class="btn btn-secondary">Back To Tournaments</a>
        <a href="{{ route('panel.matches.index') }}" class="btn">Open Match Monitor</a>
    </div>
</div>

<div class="stats">
    <div class="stat-card"><div class="stat-label">Registrations</div><div class="stat-value">{{ $stats['total_players'] }}</div></div>
    <div class="stat-card"><div class="stat-label">Completed Matches</div><div class="stat-value">{{ $stats['completed_matches'] }}</div></div>
    <div class="stat-card"><div class="stat-label">Pending Matches</div><div class="stat-value">{{ $stats['pending_matches'] }}</div></div>
    <div class="stat-card"><div class="stat-label">Prize Pool</div><div class="stat-value">₹{{ number_format((float) $tournament->total_prize_pool, 0) }}</div></div>
    <div class="stat-card"><div class="stat-label">Platform Fee</div><div class="stat-value">₹{{ number_format((float) $tournament->platform_fee_amount, 0) }}</div></div>
    <div class="stat-card"><div class="stat-label">Override Matches</div><div class="stat-value">{{ $stats['override_matches'] }}</div></div>
</div>

<div class="tabs-bar">
    <a href="#overview" class="tab-chip">Overview</a>
    <a href="#winners" class="tab-chip">Winners</a>
    <a href="#registrations" class="tab-chip">Registrations</a>
    <a href="#matches" class="tab-chip">Round-Wise Matches</a>
    <a href="#financials" class="tab-chip">Financials</a>
</div>

<section id="overview" class="panel report-section">
    <div class="section-title">Overview</div>
    <div class="details-grid">
        <div><span>Created On</span><strong><span data-utc-time="{{ $tournament->created_at?->toIso8601String() }}">{{ $tournament->created_at?->format('d M Y, h:i A') ?? '—' }}</span></strong></div>
        <div><span>Tournament Start</span><strong><span data-utc-time="{{ $tournament->tournament_start_at?->toIso8601String() }}">{{ $tournament->tournament_start_at?->format('d M Y, h:i A') ?? '—' }}</span></strong></div>
        <div><span>Completed On</span><strong><span data-utc-time="{{ $tournament->completed_at?->toIso8601String() }}">{{ $tournament->completed_at?->format('d M Y, h:i A') ?? '—' }}</span></strong></div>
        <div><span>Entry Fee</span><strong>₹{{ number_format((float) $tournament->entry_fee, 2) }}</strong></div>
        <div><span>Players Per Match</span><strong>{{ $tournament->players_per_match }}</strong></div>
        <div><span>Approved</span><strong>{{ $tournament->is_approved ? 'Yes' : 'No' }}</strong></div>
        <div><span>Real Players</span><strong>{{ $stats['real_players'] }}</strong></div>
        <div><span>Bot Players</span><strong>{{ $stats['bot_players'] }}</strong></div>
        <div><span>Cancelled Matches</span><strong>{{ $stats['cancelled_matches'] }}</strong></div>
    </div>
    @if(!empty($tournament->play_slots))
        <div class="note-box">
            <div class="note-title">Playing Slots</div>
            <div class="stack-compact">
                @foreach($tournament->play_slots as $slot)
                    <div>{{ $slot['label'] ?? 'Slot' }}: <span data-utc-time="{{ \Illuminate\Support\Carbon::parse($slot['start_at'])->toIso8601String() }}">{{ \Illuminate\Support\Carbon::parse($slot['start_at'])->format('d M Y, h:i A') }}</span> to <span data-utc-time="{{ \Illuminate\Support\Carbon::parse($slot['end_at'])->toIso8601String() }}">{{ \Illuminate\Support\Carbon::parse($slot['end_at'])->format('d M Y, h:i A') }}</span></div>
                @endforeach
            </div>
        </div>
    @endif
    @if($tournament->description)
        <div class="note-box">
            <div class="note-title">Description</div>
            <div>{{ $tournament->description }}</div>
        </div>
    @endif
</section>

<section id="winners" class="panel report-section">
    <div class="section-title">Winners & Payouts</div>
    <div class="table-wrap">
        <table>
            <thead>
            <tr>
                <th>Position</th>
                <th>Winner</th>
                <th>User ID</th>
                <th>Prize</th>
                <th>Payout</th>
            </tr>
            </thead>
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
                <tr><td colspan="5" class="muted">No prize rows found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</section>

<section id="registrations" class="panel report-section">
    <div class="section-title">Registrations</div>
    <div class="table-wrap">
        <table>
            <thead>
            <tr>
                <th>Player</th>
                <th>User ID</th>
                <th>Status</th>
                <th>Position</th>
                <th>Prize Won</th>
                <th>Registered</th>
                <th>Eliminated</th>
            </tr>
            </thead>
            <tbody>
            @forelse($registrations as $registration)
                <tr>
                    <td>{{ $registration->displayName() }}</td>
                    <td>{{ $registration->user?->user_code ?? 'Bot' }}</td>
                    <td>{{ ucwords(str_replace('_', ' ', $registration->status)) }}</td>
                    <td>{{ $registration->final_position ? '#' . $registration->final_position : '—' }}</td>
                    <td>{{ (float) $registration->prize_won > 0 ? '₹' . number_format((float) $registration->prize_won, 2) : '—' }}</td>
                    <td><span data-utc-time="{{ $registration->registered_at?->toIso8601String() }}">{{ $registration->registered_at?->format('d M Y, h:i A') ?? '—' }}</span></td>
                    <td><span data-utc-time="{{ $registration->eliminated_at?->toIso8601String() }}">{{ $registration->eliminated_at?->format('d M Y, h:i A') ?? '—' }}</span></td>
                </tr>
            @empty
                <tr><td colspan="7" class="muted">No registrations found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</section>

<section id="matches" class="panel report-section">
    <div class="section-title">Round-Wise Match Report</div>
    <div class="round-stack">
        @forelse($rounds as $round)
            <div class="round-card">
                <div class="round-head">
                    <div>
                        <strong>Round {{ $round['round_number'] }}</strong>
                        <div class="muted" style="font-size:13px;">
                            {{ $round['completed_matches'] }} completed · {{ $round['pending_matches'] }} pending · {{ $round['cancelled_matches'] }} cancelled
                        </div>
                    </div>
                    <span class="badge">{{ $round['total_matches'] }} matches</span>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead>
                        <tr>
                            <th>Match</th>
                            <th>Status</th>
                            <th>Players</th>
                            <th>Winner</th>
                            <th>Scheduled</th>
                            <th>Ended</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($round['matches'] as $match)
                            <tr>
                                <td>
                                    #{{ $match->match_number }}
                                    @if($match->is_admin_override)
                                        <div class="muted" style="font-size:12px;">Winner forced manually</div>
                                    @endif
                                </td>
                                <td>{{ ucwords(str_replace('_', ' ', $match->status)) }}</td>
                                <td>
                                    @if($match->players->isNotEmpty())
                                        {{ $match->players->map(fn ($player) => $player->registration?->displayName() ?? 'Unknown')->join(', ') }}
                                    @else
                                        <span class="muted">No players</span>
                                    @endif
                                </td>
                                <td>{{ $match->winner?->displayName() ?? $match->forcedWinner?->displayName() ?? 'Pending' }}</td>
                                <td><span data-utc-time="{{ $match->scheduled_at?->toIso8601String() }}">{{ $match->scheduled_at?->format('d M Y, h:i A') ?? '—' }}</span></td>
                                <td><span data-utc-time="{{ $match->ended_at?->toIso8601String() }}">{{ $match->ended_at?->format('d M Y, h:i A') ?? '—' }}</span></td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @empty
            <div class="muted">No match rounds found.</div>
        @endforelse
    </div>
</section>

<section id="financials" class="panel report-section">
    <div class="section-title">Financials</div>
    <div class="details-grid" style="margin-bottom:18px;">
        <div><span>Gross Entry</span><strong>₹{{ number_format((float) $stats['gross_entry'], 2) }}</strong></div>
        <div><span>Prize Pool</span><strong>₹{{ number_format((float) $tournament->total_prize_pool, 2) }}</strong></div>
        <div><span>Platform Fee</span><strong>₹{{ number_format((float) $tournament->platform_fee_amount, 2) }}</strong></div>
        <div><span>Paid / Planned Payout</span><strong>₹{{ number_format((float) $stats['prize_paid'], 2) }}</strong></div>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
            <tr>
                <th>Time</th>
                <th>User</th>
                <th>Type</th>
                <th>Amount</th>
                <th>Description</th>
            </tr>
            </thead>
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
                <tr><td colspan="5" class="muted">No tournament wallet transactions found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</section>
@endsection

@push('scripts')
<script>
const userReportTimezone = (Intl.DateTimeFormat().resolvedOptions().timeZone || 'UTC');
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
        timeZone: userReportTimezone,
        timeZoneName: 'short',
    }).format(date);
});
</script>
@endpush
