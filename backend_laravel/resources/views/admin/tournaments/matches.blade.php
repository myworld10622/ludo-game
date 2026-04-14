@extends('admin.layouts.app')

@section('title', 'Match Monitor')
@section('heading', 'Match Monitor')
@section('subheading', 'Live game tables — running, completed, and winner overrides')

@section('content')
<style>
    .monitor-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px; }
    .match-card { background: var(--card2); border: 1px solid var(--line-dim); border-radius: 12px; padding: 14px 16px; transition: border-color .15s; }
    .match-card:hover { border-color: rgba(255,215,0,0.2); }
    .match-card.running { border-left: 3px solid var(--blue); }
    .match-card.completed { border-left: 3px solid var(--green); }
    .match-card.override { border-left: 3px solid #FF9500; }
    .mc-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; }
    .mc-title { font-weight: 700; font-size: 14px; color: var(--text); }
    .mc-sub { font-size: 12px; color: var(--muted); margin-bottom: 8px; }
    .mc-players { display: flex; gap: 6px; flex-wrap: wrap; margin-bottom: 10px; }
    .player-chip { padding: 3px 10px; border-radius: 6px; font-size: 12px; font-weight: 600;
                   background: rgba(26,107,255,0.12); color: #66AAFF; border: 1px solid rgba(26,107,255,0.2); }
    .player-chip.bot { background: rgba(255,255,255,0.05); color: var(--muted); border-color: var(--line-dim); }
    .player-chip.winner { background: rgba(6,214,160,0.12); color: var(--green); border: 1px solid rgba(6,214,160,0.25); }
    .force-form { display: flex; gap: 6px; align-items: center; flex-wrap: wrap; margin-top: 8px;
                  padding-top: 8px; border-top: 1px solid var(--line-dim); }
    .force-form select { flex: 1; min-width: 140px; }
    .force-form input[type=text] { flex: 1; min-width: 120px; }
    .force-form button { padding: 7px 16px; font-size: 13px; border-radius: 8px; border: 0; cursor: pointer;
                         background: linear-gradient(135deg, #FF9500, #d97706); color: #000; font-weight: 700; white-space: nowrap;
                         box-shadow: 0 4px 12px rgba(255,149,0,0.25); }
    .status-dot { display: inline-block; width: 8px; height: 8px; border-radius: 50%; margin-right: 4px; }
    .dot-run  { background: var(--blue); }
    .dot-wait { background: #f59e0b; }
    .dot-sched{ background: var(--muted); }
    .dot-done { background: var(--green); }
    .section-head { font-size: 16px; font-weight: 800; margin: 20px 0 10px; color: var(--text); }
    .override-badge { display: inline-block; padding: 2px 8px; border-radius: 6px; font-size: 11px;
                      font-weight: 700; background: rgba(255,149,0,0.12); color: #FF9500; border: 1px solid rgba(255,149,0,0.25); margin-left: 6px; }
    @media(max-width:900px){ .monitor-grid { grid-template-columns: 1fr; } }
</style>

@if (session('status'))
    <div class="flash">{{ session('status') }}</div>
@endif
@if (session('error'))
    <div style="background:#fee4e2;border:1px solid #fecdca;color:#b42318;padding:12px 14px;border-radius:10px;margin-bottom:16px;">
        {{ session('error') }}
    </div>
@endif

{{-- ── Stats Bar ────────────────────────────────────────────────────────────── --}}
<div class="stats" style="margin-bottom:16px;">
    <div class="stat-card">
        <div class="stat-label">🟢 Running Matches</div>
        <div class="stat-value">{{ $stats['running'] }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">✅ Completed (all time)</div>
        <div class="stat-value">{{ number_format($stats['completed_total']) }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">⚡ Admin Overrides</div>
        <div class="stat-value">{{ $stats['overridden'] }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">🏆 Live Tournaments</div>
        <div class="stat-value">{{ $stats['tournaments_live'] }}</div>
    </div>
</div>

{{-- ── Running / Scheduled Matches ─────────────────────────────────────────── --}}
<div class="section-head">
    ⚡ Active Game Tables
    <span class="muted" style="font-size:13px;font-weight:400;">({{ $runningMatches->count() }} total)</span>
</div>

@if ($runningMatches->isEmpty())
    <div class="panel muted" style="padding:20px;text-align:center;">No active game tables right now.</div>
@else
    <div class="monitor-grid">
    @foreach ($runningMatches as $match)
        @php
            $dotClass = match($match->status) {
                'in_progress' => 'dot-run',
                'waiting'     => 'dot-wait',
                default       => 'dot-sched',
            };
            $duration = $match->started_at
                ? now()->diffForHumans($match->started_at, true)
                : '—';
            $hasForced = (bool) $match->forced_winner_registration_id;
        @endphp
        <div class="match-card running {{ $hasForced ? 'override' : '' }}">
            <div class="mc-header">
                <span class="mc-title">
                    <span class="status-dot {{ $dotClass }}"></span>
                    R{{ $match->round_number }} · Match #{{ $match->match_number }}
                    @if($hasForced)<span class="override-badge">WINNER SET</span>@endif
                </span>
                <span class="muted" style="font-size:12px;">{{ ucfirst($match->status) }}</span>
            </div>
            <div class="mc-sub">
                🏆 {{ $match->tournament->name ?? "T#{$match->tournament_id}" }}
                &nbsp;·&nbsp; ⏱ {{ $duration }}
            </div>
            <div class="mc-players">
                @foreach ($match->players as $mp)
                    @php
                        $reg = $mp->registration;
                        $isBot = $reg?->is_bot;
                        $label = $isBot
                            ? ($reg->bot_name ?? "Bot#{$reg->id}")
                            : ($reg?->user?->username ?? "User#{$reg?->user_id}");
                        $isForced = $match->forced_winner_registration_id == $reg?->id;
                    @endphp
                    <span class="player-chip {{ $isBot ? 'bot' : '' }} {{ $isForced ? 'winner' : '' }}">
                        {{ $label }}{{ $isForced ? ' 🎯' : '' }}
                    </span>
                @endforeach
            </div>

            {{-- Force Winner Form --}}
            <form method="POST" action="{{ route('admin.matches.force-winner', $match) }}" class="force-form">
                @csrf
                <select name="registration_id" required>
                    <option value="">— Set winner —</option>
                    @foreach ($match->players as $mp)
                        @php
                            $reg = $mp->registration;
                            $lbl = $reg?->is_bot
                                ? ($reg->bot_name ?? "Bot#{$reg->id}")
                                : ($reg?->user?->username ?? "User#{$reg?->user_id}");
                        @endphp
                        <option value="{{ $reg?->id }}"
                            {{ $match->forced_winner_registration_id == $reg?->id ? 'selected' : '' }}>
                            {{ $lbl }}
                        </option>
                    @endforeach
                </select>
                <input type="text" name="note" placeholder="Reason (optional)" maxlength="255">
                <button type="submit">Set Winner</button>
            </form>
        </div>
    @endforeach
    </div>
@endif

{{-- ── Completed Matches ────────────────────────────────────────────────────── --}}
<div class="section-head" style="margin-top:28px;">
    ✅ Recently Completed
    <span class="muted" style="font-size:13px;font-weight:400;">(last 100)</span>
</div>

<div class="panel" style="padding:0;overflow:hidden;">
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Tournament</th>
                    <th>Round</th>
                    <th>Match</th>
                    <th>Players</th>
                    <th>Winner</th>
                    <th>Override?</th>
                    <th>Ended</th>
                    <th>Duration</th>
                </tr>
            </thead>
            <tbody>
            @forelse ($completedMatches as $match)
                @php
                    $winnerReg = $match->winner;
                    $winnerName = $winnerReg
                        ? ($winnerReg->is_bot
                            ? ($winnerReg->bot_name ?? "Bot#{$winnerReg->id}")
                            : ($winnerReg->user?->username ?? "User#{$winnerReg->user_id}"))
                        : '—';
                    $playerList = $match->players->map(function($mp) {
                        $reg = $mp->registration;
                        return $reg?->is_bot
                            ? ($reg->bot_name ?? "Bot#{$reg->id}")
                            : ($reg?->user?->username ?? "User#{$reg?->user_id}");
                    })->join(' vs ');
                    $dur = ($match->started_at && $match->ended_at)
                        ? $match->started_at->diff($match->ended_at)->format('%im %ss')
                        : '—';
                @endphp
                <tr>
                    <td style="max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                        {{ $match->tournament->name ?? "T#{$match->tournament_id}" }}
                    </td>
                    <td>R{{ $match->round_number }}</td>
                    <td>#{{ $match->match_number }}</td>
                    <td style="font-size:13px;">{{ $playerList ?: '—' }}</td>
                    <td>
                        @if($winnerReg && !$winnerReg->is_bot)
                            <strong style="color:#065f46;">{{ $winnerName }}</strong>
                        @else
                            <span class="muted">{{ $winnerName }}</span>
                        @endif
                    </td>
                    <td>
                        @if($match->is_admin_override)
                            <span class="badge" style="background:#fef3c7;color:#92400e;" title="{{ $match->admin_override_note }}">Override</span>
                        @else
                            <span class="muted">—</span>
                        @endif
                    </td>
                    <td style="font-size:12px;" class="muted">
                        {{ $match->ended_at?->format('M d H:i') ?? '—' }}
                    </td>
                    <td style="font-size:12px;" class="muted">{{ $dur }}</td>
                </tr>
            @empty
                <tr><td colspan="8" class="muted">No completed matches yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection
