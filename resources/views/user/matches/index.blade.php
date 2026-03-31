@extends('user.layouts.app')

@section('title', 'Match Monitor')
@section('heading', 'Match Monitor')
@section('subheading', 'Only matches from tournaments created by you are visible here')

@section('content')
<style>
    .monitor-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px; }
    .match-card { background: #fff; border: 1px solid #d9e1e7; border-radius: 12px; padding: 14px 16px; }
    .match-card.running { border-left: 4px solid #2563eb; }
    .match-card.override { border-left: 4px solid #d97706; }
    .mc-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; }
    .mc-title { font-weight: 700; font-size: 14px; }
    .mc-sub { font-size: 12px; color: #5b6670; margin-bottom: 8px; }
    .mc-players { display: flex; gap: 6px; flex-wrap: wrap; margin-bottom: 10px; }
    .player-chip { padding: 3px 8px; border-radius: 6px; font-size: 12px; font-weight: 600; background: #e0f2fe; color: #0369a1; }
    .player-chip.bot { background: #f3f4f6; color: #6b7280; }
    .player-chip.winner { background: #d1fae5; color: #065f46; }
    .force-form { display: flex; gap: 6px; align-items: center; flex-wrap: wrap; margin-top: 8px; padding-top: 8px; border-top: 1px solid #e5e7eb; }
    .force-form select, .force-form input[type=text] { flex: 1; min-width: 140px; border: 1px solid #d1d5db; border-radius: 8px; padding: 6px 8px; font-size: 13px; }
    .force-form button { padding: 6px 14px; font-size: 13px; border-radius: 8px; border: 0; cursor: pointer; background: #d97706; color: #fff; white-space: nowrap; }
    .override-badge { display: inline-block; padding: 2px 6px; border-radius: 4px; font-size: 11px; font-weight: 700; background: #fef3c7; color: #92400e; margin-left: 6px; }
    .section-head { font-size: 16px; font-weight: 700; margin: 20px 0 10px; }
    @media (max-width: 900px) { .monitor-grid { grid-template-columns: 1fr; } }
</style>

<div class="stats" style="margin-bottom:16px;">
    <div class="stat-card">
        <div class="stat-label">Running Matches</div>
        <div class="stat-value">{{ $stats['running'] }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Completed</div>
        <div class="stat-value">{{ number_format($stats['completed_total']) }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Overrides</div>
        <div class="stat-value">{{ $stats['overridden'] }}</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Live Tournaments</div>
        <div class="stat-value">{{ $stats['tournaments_live'] }}</div>
    </div>
</div>

<div class="section-head">Active Game Tables</div>

@if ($runningMatches->isEmpty())
    <div class="panel muted" style="padding:20px;text-align:center;">No active game tables right now.</div>
@else
    <div class="monitor-grid">
        @foreach ($runningMatches as $match)
            @php
                $hasForcedWinner = (bool) $match->forced_winner_registration_id;
            @endphp
            <div class="match-card running {{ $hasForcedWinner ? 'override' : '' }}">
                <div class="mc-header">
                    <span class="mc-title">
                        R{{ $match->round_number }} · Match #{{ $match->match_number }}
                        @if ($hasForcedWinner)
                            <span class="override-badge">WINNER SET</span>
                        @endif
                    </span>
                    <span class="muted" style="font-size:12px;">{{ ucfirst($match->status) }}</span>
                </div>

                <div class="mc-sub">
                    Tournament: {{ $match->tournament->name ?? "T#{$match->tournament_id}" }}
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

                <form method="POST" action="{{ route('panel.matches.force-winner', $match) }}" class="force-form">
                    @csrf
                    <select name="registration_id" required>
                        <option value="">Set winner</option>
                        @foreach ($match->players as $mp)
                            @php
                                $reg = $mp->registration;
                                $label = $reg?->is_bot
                                    ? ($reg->bot_name ?? "Bot#{$reg->id}")
                                    : ($reg?->user?->username ?? "User#{$reg?->user_id}");
                            @endphp
                            <option value="{{ $reg?->id }}" {{ $match->forced_winner_registration_id == $reg?->id ? 'selected' : '' }}>
                                {{ $label }}
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

<div class="section-head">Recently Completed</div>

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
                        $playerList = $match->players->map(function ($mp) {
                            $reg = $mp->registration;
                            return $reg?->is_bot
                                ? ($reg->bot_name ?? "Bot#{$reg->id}")
                                : ($reg?->user?->username ?? "User#{$reg?->user_id}");
                        })->join(' vs ');
                    @endphp
                    <tr>
                        <td>{{ $match->tournament->name ?? "T#{$match->tournament_id}" }}</td>
                        <td>R{{ $match->round_number }}</td>
                        <td>#{{ $match->match_number }}</td>
                        <td>{{ $playerList ?: '—' }}</td>
                        <td>{{ $winnerName }}</td>
                        <td>
                            @if ($match->is_admin_override)
                                <span class="badge" style="background:#fef3c7;color:#92400e;" title="{{ $match->admin_override_note }}">
                                    Override
                                </span>
                            @else
                                <span class="muted">—</span>
                            @endif
                        </td>
                        <td class="muted">{{ $match->ended_at?->format('M d H:i') ?? '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="muted">No completed matches yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
