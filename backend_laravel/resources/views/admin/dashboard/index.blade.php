@extends('admin.layouts.app')

@section('title', 'Dashboard')
@section('heading', 'Dashboard')
@section('subheading', 'Advanced control room for tournaments, revenue, users, and reports')

@section('content')
@php($liveOrRunningTournaments = $recent_tournaments->filter(fn ($tournament) => in_array($tournament->status, ['registration_open', 'in_progress']))->values())
@php($runningTournamentCount = $recent_tournaments->where('status', 'in_progress')->count())
<div class="stack">
    <div class="panel" style="background:linear-gradient(135deg,rgba(255,215,0,0.14),rgba(255,149,0,0.12) 48%,rgba(6,214,160,0.12) 100%);border-color:rgba(255,215,0,0.2);">
        <div style="display:flex;justify-content:space-between;gap:18px;align-items:flex-start;flex-wrap:wrap;">
            <div>
                <div class="badge" style="background:rgba(255,255,255,0.14);color:#fff;">Admin Command Center</div>
                <h2 style="margin:12px 0 8px;font-size:32px;">Live business snapshot with direct tournament reports</h2>
                <div style="max-width:900px;color:rgba(255,255,255,0.86);line-height:1.7;">
                    Track platform activity, tournament status, payouts, recent report openings, and high-value user accounts from one dashboard.
                </div>
            </div>
            <div class="live-callout">
                <span class="live-pill">LIVE</span>
                <div style="font-size:28px;font-weight:800;margin-top:10px;">{{ $tournamentStats['live'] }}</div>
                <div style="font-size:13px;opacity:0.85;">Active tournaments now</div>
            </div>
        </div>
    </div>

    <div class="highlight-grid">
        <div class="highlight-card live-card">
            <div class="highlight-top">
                <span class="live-pill">LIVE</span>
                <a href="{{ route('admin.tournaments.index') }}" class="text-link">Open Tournaments</a>
            </div>
            <div class="highlight-value">{{ $tournamentStats['live'] }}</div>
            <div class="highlight-label">Live Or Registration Open Tournaments</div>
            <div class="highlight-sub">{{ $tournamentStats['pending_approval'] }} tournaments are still waiting for admin approval.</div>
        </div>
        <div class="highlight-card running-card">
            <div class="highlight-top">
                <span class="running-pill">RUNNING</span>
                <a href="{{ route('admin.tournaments.matches') }}" class="text-link">Open Match Monitor</a>
            </div>
            <div class="highlight-value">{{ $runningTournamentCount }}</div>
            <div class="highlight-label">Tournaments Currently In Progress</div>
            <div class="highlight-sub">{{ $tournamentStats['completed'] }} tournaments are already completed across the platform.</div>
        </div>
    </div>

    <div class="stats">
        @foreach ($stats as $label => $value)
            <div class="stat-card">
                <div class="stat-label">{{ str($label)->replace('_', ' ')->title() }}</div>
                <div class="stat-value">{{ $value }}</div>
            </div>
        @endforeach
    </div>

    <div class="panel">
        <div class="header-row">
            <strong>Live And Running Tournaments</strong>
            <a class="text-link" href="{{ route('admin.tournaments.index') }}">Manage All</a>
        </div>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:14px;">
            @forelse($liveOrRunningTournaments as $tournament)
                <div class="panel" style="background:var(--card2);border-color:rgba(255,215,0,0.15);">
                    <div style="display:flex;justify-content:space-between;gap:12px;align-items:flex-start;">
                        <div>
                            <div style="font-size:18px;font-weight:800;color:var(--text);">{{ $tournament->name }}</div>
                            <div class="muted" style="font-size:12px;">
                                {{ ucfirst($tournament->creator_type) }}
                                @if($tournament->creator_type === 'user' && $tournament->creator)
                                    · {{ $tournament->creator->username }} ({{ $tournament->creator->user_code }})
                                @endif
                            </div>
                        </div>
                        <span class="{{ $tournament->status === 'in_progress' ? 'running-pill' : 'live-pill' }}">
                            {{ $tournament->status === 'in_progress' ? 'RUNNING' : 'LIVE' }}
                        </span>
                    </div>
                    <div style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px;margin-top:14px;">
                        <div><div class="stat-label">Players</div><div style="font-weight:800;color:var(--text);">{{ $tournament->registrations_count }}/{{ $tournament->max_players }}</div></div>
                        <div><div class="stat-label">Running</div><div style="font-weight:800;color:var(--text);">{{ $tournament->pending_matches_count }}</div></div>
                        <div><div class="stat-label">Completed</div><div style="font-weight:800;color:var(--text);">{{ $tournament->completed_matches_count }}</div></div>
                    </div>
                    <div class="mobile-actions" style="margin-top:14px;">
                        <a class="btn" href="{{ route('admin.tournaments.report', $tournament) }}">Open Report</a>
                        <a class="btn btn-secondary" href="{{ route('admin.tournaments.edit', $tournament) }}">Edit</a>
                    </div>
                </div>
            @empty
                <div class="muted">No live or running tournaments right now.</div>
            @endforelse
        </div>
    </div>

    <div class="panel">
        <div class="header-row">
            <strong>Pending Tournament Approval Alerts</strong>
            <a class="muted" href="{{ route('admin.tournaments.index') }}">Open tournament queue</a>
        </div>
        <div class="table-wrap responsive-table">
            <table>
                <thead>
                    <tr>
                        <th>Tournament</th>
                        <th>User</th>
                        <th>Created</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($pending_approval_tournaments as $tournament)
                        <tr>
                            <td data-label="Tournament">
                                <strong>{{ $tournament->name }}</strong>
                                <div class="muted" style="font-size:12px;">{{ ucfirst($tournament->type) }} · {{ ucwords(str_replace('_',' ', $tournament->format)) }}</div>
                            </td>
                            <td data-label="User">{{ $tournament->creator?->username ?? 'User' }} · {{ $tournament->creator?->user_code ?? '—' }}</td>
                            <td data-label="Created">{{ $tournament->created_at?->format('d M Y, h:i A') ?? '—' }}</td>
                            <td data-label="Status"><span class="badge">Pending Approval</span></td>
                            <td data-label="Actions" style="white-space:nowrap;">
                                <a class="btn btn-secondary" style="font-size:12px;padding:6px 10px;margin-right:4px;" href="{{ route('admin.tournaments.report', $tournament) }}">Review Details</a>
                                <a class="btn btn-secondary" style="font-size:12px;padding:6px 10px;margin-right:4px;" href="{{ route('admin.tournaments.edit', $tournament) }}">Edit</a>
                                <form method="POST" action="{{ route('admin.tournaments.approve', $tournament) }}" style="display:inline;">@csrf<button type="submit" class="btn" style="font-size:12px;padding:6px 10px;margin-right:4px;">Approve</button></form>
                                <form method="POST" action="{{ route('admin.tournaments.reject', $tournament) }}" style="display:inline;" onsubmit="return confirm('Reject this tournament? A support ticket will be sent to the user.')">
                                    @csrf
                                    <input type="hidden" name="reason" value="Tournament needs admin review updates before approval. Please check support ticket for details.">
                                    <button type="submit" class="btn btn-secondary" style="font-size:12px;padding:6px 10px;">Reject</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="muted">No pending tournament approval alerts right now.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="split-main-aside">
        <div class="panel">
            <div class="header-row">
                <strong>Tournament Report Snapshot</strong>
                <a class="muted" href="{{ route('admin.tournaments.index') }}">Open tournaments</a>
            </div>
            <div style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px;">
                <div style="padding:14px;border:1px solid var(--line-dim);border-radius:14px;background:var(--card2);">
                    <div class="stat-label">Live</div>
                    <div style="font-size:24px;font-weight:800;color:var(--gold);">{{ $tournamentStats['live'] }}</div>
                </div>
                <div style="padding:14px;border:1px solid var(--line-dim);border-radius:14px;background:var(--card2);">
                    <div class="stat-label">Completed</div>
                    <div style="font-size:24px;font-weight:800;color:var(--gold);">{{ $tournamentStats['completed'] }}</div>
                </div>
                <div style="padding:14px;border:1px solid var(--line-dim);border-radius:14px;background:var(--card2);">
                    <div class="stat-label">Drafts</div>
                    <div style="font-size:24px;font-weight:800;color:var(--gold);">{{ $tournamentStats['drafts'] }}</div>
                </div>
                <div style="padding:14px;border:1px solid var(--line-dim);border-radius:14px;background:var(--card2);">
                    <div class="stat-label">User Created</div>
                    <div style="font-size:24px;font-weight:800;color:var(--green);">{{ $tournamentStats['user_created'] }}</div>
                </div>
                <div style="padding:14px;border:1px solid var(--line-dim);border-radius:14px;background:var(--card2);">
                    <div class="stat-label">Admin Created</div>
                    <div style="font-size:24px;font-weight:800;color:var(--green);">{{ $tournamentStats['admin_created'] }}</div>
                </div>
                <div style="padding:14px;border:1px solid rgba(230,57,70,0.2);border-radius:14px;background:rgba(230,57,70,0.06);">
                    <div class="stat-label">Pending Approval</div>
                    <div style="font-size:24px;font-weight:800;color:var(--red);">{{ $tournamentStats['pending_approval'] }}</div>
                </div>
            </div>
        </div>

        <div class="panel">
            <div class="header-row">
                <strong>Revenue Snapshot</strong>
                <span class="muted">Platform financial view</span>
            </div>
            <div style="display:grid;gap:12px;">
                <div style="padding:14px;border:1px solid var(--line-dim);border-radius:14px;background:var(--card2);">
                    <div class="stat-label">Wallet Volume</div>
                    <div style="font-size:26px;font-weight:800;color:var(--gold);">₹{{ number_format($revenue['wallet_volume'], 2) }}</div>
                </div>
                <div style="padding:14px;border:1px solid var(--line-dim);border-radius:14px;background:var(--card2);">
                    <div class="stat-label">Active Wallet Balance</div>
                    <div style="font-size:26px;font-weight:800;color:var(--green);">₹{{ number_format($revenue['active_wallet_balance'], 2) }}</div>
                </div>
                <div style="padding:14px;border:1px solid rgba(255,149,0,0.2);border-radius:14px;background:rgba(255,149,0,0.06);">
                    <div class="stat-label">Tournament Platform Fee</div>
                    <div style="font-size:26px;font-weight:800;color:#FF9500;">₹{{ number_format($revenue['tournament_platform_fee'], 2) }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="split-main-aside">
        <div class="panel">
            <div class="header-row">
                <strong>Recent Tournament Reports</strong>
                <a class="muted" href="{{ route('admin.tournaments.index') }}">See all tournaments</a>
            </div>
            <div class="table-wrap responsive-table">
                <table>
                    <thead>
                        <tr>
                            <th>Tournament</th>
                            <th>Owner</th>
                            <th>Status</th>
                            <th>Players</th>
                            <th>Matches</th>
                            <th>Report</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($recent_tournaments as $tournament)
                            <tr>
                                <td data-label="Tournament">
                                    <strong>{{ $tournament->name }}</strong>
                                    <div class="muted" style="font-size:12px;">{{ $tournament->created_at?->format('d M Y, h:i A') ?? '—' }}</div>
                                </td>
                                <td data-label="Owner">
                                    {{ ucfirst($tournament->creator_type) }}
                                    @if($tournament->creator_type === 'user' && $tournament->creator)
                                        <div class="muted" style="font-size:12px;">{{ $tournament->creator->username }} · {{ $tournament->creator->user_code }}</div>
                                    @endif
                                </td>
                                <td data-label="Status"><span class="badge">{{ ucwords(str_replace('_', ' ', $tournament->status)) }}</span></td>
                                <td data-label="Players">{{ $tournament->registrations_count }}/{{ $tournament->max_players }}</td>
                                <td data-label="Matches">{{ $tournament->completed_matches_count }} complete · {{ $tournament->pending_matches_count }} pending</td>
                                <td data-label="Report"><a class="btn btn-secondary" style="font-size:12px;padding:6px 10px;" href="{{ route('admin.tournaments.report', $tournament) }}">Open Report</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="muted">No tournament activity yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="panel">
            <div class="header-row">
                <strong>Top User Activity</strong>
                <a class="muted" href="{{ route('admin.users.index') }}">Open users</a>
            </div>
            <div style="display:grid;gap:10px;">
                @forelse($top_users as $user)
                    <div style="padding:14px;border:1px solid var(--line-dim);border-radius:14px;background:var(--card2);">
                        <div style="display:flex;justify-content:space-between;gap:10px;align-items:flex-start;">
                            <div>
                                <div style="font-weight:800;color:var(--text);">{{ $user->username }}</div>
                                <div class="muted" style="font-size:12px;">{{ $user->user_code }} · {{ $user->email ?: ($user->mobile ?: 'No contact') }}</div>
                            </div>
                            <a href="{{ route('admin.users.show', $user) }}" class="badge">Open</a>
                        </div>
                        <div style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:8px;margin-top:12px;">
                            <div><div class="stat-label">Registrations</div><div style="font-weight:800;color:var(--text);">{{ $user->tournament_registrations_count }}</div></div>
                            <div><div class="stat-label">Created</div><div style="font-weight:800;color:var(--text);">{{ $user->created_tournaments_count }}</div></div>
                            <div><div class="stat-label">Wallet</div><div style="font-weight:800;color:var(--green);">₹{{ number_format((float) ($user->primaryWallet?->balance ?? 0), 0) }}</div></div>
                        </div>
                    </div>
                @empty
                    <div class="muted">No user activity available yet.</div>
                @endforelse
            </div>
        </div>
    </div>

    <div class="panel">
        <div class="header-row">
            <strong>Recent Audit Logs</strong>
            <a class="muted" href="{{ route('admin.audit-logs.index') }}">View all</a>
        </div>
        <div class="table-wrap responsive-table">
            <table>
                <thead>
                    <tr>
                        <th>Event</th>
                        <th>Source</th>
                        <th>Target</th>
                        <th>Time</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($recent_audits as $log)
                        <tr>
                            <td data-label="Event">{{ $log->event }}</td>
                            <td data-label="Source">{{ $log->source }}</td>
                            <td data-label="Target">{{ $log->auditable_type }}#{{ $log->auditable_id }}</td>
                            <td data-label="Time">{{ optional($log->created_at)->toDateTimeString() }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="muted">No audit activity available yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
