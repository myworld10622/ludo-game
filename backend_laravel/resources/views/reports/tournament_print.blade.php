<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $tournament->name }} Report</title>
    <style>
        body { font-family: "Segoe UI", sans-serif; margin: 24px; color: #111827; background: #fff; }
        h1, h2, h3 { margin: 0; }
        .topbar { display:flex; justify-content:space-between; align-items:flex-start; gap:16px; margin-bottom:20px; }
        .meta { color:#4b5563; line-height:1.7; margin-top:8px; }
        .actions { display:flex; gap:10px; flex-wrap:wrap; }
        .btn { display:inline-block; padding:10px 14px; border-radius:8px; background:#0f766e; color:#fff; text-decoration:none; border:none; cursor:pointer; }
        .btn-secondary { background:#e5e7eb; color:#111827; }
        .summary { display:grid; grid-template-columns:repeat(3, minmax(0, 1fr)); gap:12px; margin:20px 0; }
        .card { border:1px solid #d1d5db; border-radius:12px; padding:14px; }
        .label { font-size:12px; text-transform:uppercase; letter-spacing:0.06em; color:#6b7280; margin-bottom:6px; }
        .value { font-size:22px; font-weight:700; }
        .section { margin-top:24px; }
        table { width:100%; border-collapse:collapse; margin-top:10px; }
        th, td { border:1px solid #d1d5db; padding:8px 10px; text-align:left; vertical-align:top; font-size:13px; }
        th { background:#f9fafb; }
        .round-block { border:1px solid #d1d5db; border-radius:12px; padding:14px; margin-top:16px; }
        .muted { color:#6b7280; }
        @media print {
            .actions { display:none; }
            body { margin: 10mm; }
            .section, .round-block, table { page-break-inside: avoid; }
        }
        @media (max-width: 900px) {
            .summary { grid-template-columns:1fr; }
            .topbar { flex-direction:column; }
        }
    </style>
</head>
<body>
    <div class="topbar">
        <div>
            <h1>{{ $tournament->name }}</h1>
            <div class="meta">
                {{ strtoupper($panelType) }} REPORT<br>
                Status: {{ ucwords(str_replace('_', ' ', $tournament->status)) }}<br>
                Created: {{ $tournament->created_at?->format('d M Y, h:i A') ?? '—' }}<br>
                Owner:
                @if($tournament->creator_type === 'user')
                    {{ $tournament->creator?->username ?? 'User' }} ({{ $tournament->creator?->user_code ?? '—' }})
                @else
                    Admin
                @endif
                <br>
                {{ $printIntent === 'pdf' ? 'Use "Save as PDF" in the print dialog.' : 'Use your printer dialog to print this report.' }}
            </div>
        </div>
        <div class="actions">
            <a href="{{ $backUrl }}" class="btn btn-secondary">Back To Report</a>
            <button class="btn" onclick="window.print()">Print / Save PDF</button>
        </div>
    </div>

    <div class="summary">
        <div class="card"><div class="label">Registrations</div><div class="value">{{ $stats['total_players'] }}</div></div>
        <div class="card"><div class="label">Completed Matches</div><div class="value">{{ $stats['completed_matches'] }}</div></div>
        <div class="card"><div class="label">Pending Matches</div><div class="value">{{ $stats['pending_matches'] }}</div></div>
        <div class="card"><div class="label">Prize Pool</div><div class="value">₹{{ number_format((float) $tournament->total_prize_pool, 2) }}</div></div>
        <div class="card"><div class="label">Platform Fee</div><div class="value">₹{{ number_format((float) $tournament->platform_fee_amount, 2) }}</div></div>
        <div class="card"><div class="label">Override Matches</div><div class="value">{{ $stats['override_matches'] }}</div></div>
    </div>

    <div class="section">
        <h2>Winners</h2>
        <table>
            <thead><tr><th>Position</th><th>Winner</th><th>User ID</th><th>Prize</th><th>Payout</th></tr></thead>
            <tbody>
            @foreach($prizes as $prize)
                <tr>
                    <td>#{{ $prize->position }}</td>
                    <td>{{ $prize->winner?->username ?? 'Pending' }}</td>
                    <td>{{ $prize->winner?->user_code ?? '—' }}</td>
                    <td>₹{{ number_format((float) $prize->prize_amount, 2) }}</td>
                    <td>{{ ucfirst($prize->payout_status) }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>

    <div class="section">
        <h2>Registrations</h2>
        <table>
            <thead><tr><th>Player</th><th>User ID</th><th>Status</th><th>Position</th><th>Prize Won</th></tr></thead>
            <tbody>
            @foreach($registrations as $registration)
                <tr>
                    <td>{{ $registration->displayName() }}</td>
                    <td>{{ $registration->user?->user_code ?? 'Bot' }}</td>
                    <td>{{ ucwords(str_replace('_', ' ', $registration->status)) }}</td>
                    <td>{{ $registration->final_position ? '#' . $registration->final_position : '—' }}</td>
                    <td>{{ (float) $registration->prize_won > 0 ? '₹' . number_format((float) $registration->prize_won, 2) : '—' }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>

    <div class="section">
        <h2>Round-Wise Matches</h2>
        @foreach($rounds as $round)
            <div class="round-block">
                <h3>Round {{ $round['round_number'] }}</h3>
                <div class="muted">{{ $round['completed_matches'] }} completed · {{ $round['pending_matches'] }} pending · {{ $round['cancelled_matches'] }} cancelled</div>
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
        @endforeach
    </div>

    <div class="section">
        <h2>Financials</h2>
        <table>
            <thead><tr><th>Time</th><th>User</th><th>Type</th><th>Amount</th><th>Description</th></tr></thead>
            <tbody>
            @foreach($financialRows as $row)
                <tr>
                    <td>{{ $row->created_at?->format('d M Y, h:i A') ?? '—' }}</td>
                    <td>{{ $row->user?->username ?? 'System' }}</td>
                    <td>{{ ucwords(str_replace('_', ' ', $row->type ?? 'transaction')) }}</td>
                    <td>₹{{ number_format((float) ($row->amount ?? 0), 2) }}</td>
                    <td>{{ $row->description ?? '—' }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>

    <script>
        window.addEventListener('load', function () {
            window.print();
        });
    </script>
</body>
</html>
