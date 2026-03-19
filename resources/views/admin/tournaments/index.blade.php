@extends('admin.layouts.app')

@section('title', 'Tournaments')
@section('heading', 'Tournaments')
@section('subheading', 'Create, publish, cancel, and maintain tournament inventory')

@php
    $isEdit = (bool) $editingTournament;
    $formAction = $isEdit ? route('admin.tournaments.update', $editingTournament) : route('admin.tournaments.store');
    $statusOptions = ['draft', 'published', 'entry_open', 'entry_locked', 'seeding', 'running', 'cancelled', 'completed'];
    $visibilityOptions = ['public', 'private'];
    $typeOptions = ['knockout'];
    $seedingStrategyOptions = ['random', 'ranked', 'segmented'];
    $botFillPolicyOptions = ['fill_after_timeout', 'real_only', 'never_fill'];
    $editingMeta = $editingTournament?->meta ?? [];
    $editingRules = $editingTournament?->rules ?? [];
@endphp

@section('content')
    <div class="stack">
        <div class="panel">
            <div class="header-row">
                <strong>{{ $isEdit ? 'Edit Tournament' : 'Create Tournament' }}</strong>
                @if ($isEdit)
                    <a class="btn btn-secondary" href="{{ route('admin.tournaments.index') }}">New Tournament</a>
                @endif
            </div>
            <div class="muted" style="margin-bottom:12px;">
                For Unity-visible joinable tournaments, use `published` or `entry_open` and keep the registration window open.
            </div>

            <form method="POST" action="{{ $formAction }}" class="stack">
                @csrf
                @if ($isEdit)
                    @method('PUT')
                @endif

                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:16px;">
                    <div>
                        <label>Game</label>
                        <select name="game_id" style="width:100%;padding:10px;border:1px solid #d9e1e7;border-radius:10px;">
                            @foreach ($games as $game)
                                <option value="{{ $game->id }}" @selected(old('game_id', $editingTournament?->game_id) == $game->id)>{{ $game->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label>Name</label>
                        <input name="name" value="{{ old('name', $editingTournament?->name) }}" style="width:100%;padding:10px;border:1px solid #d9e1e7;border-radius:10px;">
                    </div>
                    <div>
                        <label>Status</label>
                        <select name="status" style="width:100%;padding:10px;border:1px solid #d9e1e7;border-radius:10px;">
                            @foreach ($statusOptions as $status)
                                <option value="{{ $status }}" @selected(old('status', $editingTournament?->status ?? 'draft') === $status)>{{ ucfirst(str_replace('_', ' ', $status)) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label>Visibility</label>
                        <select name="visibility" style="width:100%;padding:10px;border:1px solid #d9e1e7;border-radius:10px;">
                            @foreach ($visibilityOptions as $visibility)
                                <option value="{{ $visibility }}" @selected(old('visibility', $editingTournament?->visibility ?? 'public') === $visibility)>{{ ucfirst($visibility) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label>Tournament Type</label>
                        <select name="tournament_type" style="width:100%;padding:10px;border:1px solid #d9e1e7;border-radius:10px;">
                            @foreach ($typeOptions as $type)
                                <option value="{{ $type }}" @selected(old('tournament_type', $editingTournament?->type ?? 'knockout') === $type)>{{ ucfirst($type) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label>Currency</label>
                        <input name="currency" value="{{ old('currency', $editingTournament?->currency ?? 'INR') }}" style="width:100%;padding:10px;border:1px solid #d9e1e7;border-radius:10px;">
                    </div>
                    <div>
                        <label>Entry Fee</label>
                        <input type="number" step="0.0001" name="entry_fee" value="{{ old('entry_fee', $editingTournament?->entry_fee ?? 0) }}" style="width:100%;padding:10px;border:1px solid #d9e1e7;border-radius:10px;">
                    </div>
                    <div>
                        <label>Max Entries Per User</label>
                        <input type="number" name="max_entries_per_user" value="{{ old('max_entries_per_user', $editingTournament?->max_entries_per_user ?? 1) }}" style="width:100%;padding:10px;border:1px solid #d9e1e7;border-radius:10px;">
                    </div>
                    <div>
                        <label>Max Total Entries</label>
                        <input type="number" name="max_total_entries" value="{{ old('max_total_entries', $editingTournament?->max_total_entries) }}" style="width:100%;padding:10px;border:1px solid #d9e1e7;border-radius:10px;">
                    </div>
                    <div>
                        <label>Min Entries Required</label>
                        <input type="number" name="min_players" value="{{ old('min_players', $editingTournament?->min_total_entries ?? 2) }}" min="2" style="width:100%;padding:10px;border:1px solid #d9e1e7;border-radius:10px;">
                    </div>
                    <div>
                        <label>Match Size</label>
                        <select name="match_size" style="width:100%;padding:10px;border:1px solid #d9e1e7;border-radius:10px;">
                            @foreach ([2, 4] as $matchSize)
                                <option value="{{ $matchSize }}" @selected((int) old('match_size', $editingTournament?->match_size ?? 4) === $matchSize)>{{ $matchSize }} Players</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label>Advance Count</label>
                        <input type="number" name="advance_count" value="{{ old('advance_count', $editingTournament?->advance_count ?? 1) }}" min="1" style="width:100%;padding:10px;border:1px solid #d9e1e7;border-radius:10px;">
                    </div>
                    <div>
                        <label>Bracket Size</label>
                        <input type="number" name="bracket_size" value="{{ old('bracket_size', $editingTournament?->bracket_size) }}" min="1" style="width:100%;padding:10px;border:1px solid #d9e1e7;border-radius:10px;">
                    </div>
                    <div>
                        <label>Seeding Strategy</label>
                        <select name="seeding_strategy" style="width:100%;padding:10px;border:1px solid #d9e1e7;border-radius:10px;">
                            @foreach ($seedingStrategyOptions as $strategy)
                                <option value="{{ $strategy }}" @selected(old('seeding_strategy', $editingTournament?->seeding_strategy ?? 'random') === $strategy)>{{ ucfirst($strategy) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label>Bot Fill Policy</label>
                        <select name="bot_fill_policy" style="width:100%;padding:10px;border:1px solid #d9e1e7;border-radius:10px;">
                            @foreach ($botFillPolicyOptions as $botFillPolicy)
                                <option value="{{ $botFillPolicy }}" @selected(old('bot_fill_policy', $editingTournament?->bot_fill_policy ?? 'fill_after_timeout') === $botFillPolicy)>{{ ucfirst(str_replace('_', ' ', $botFillPolicy)) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label>Prize Pool</label>
                        <input type="number" step="0.0001" name="prize_pool" value="{{ old('prize_pool', $editingTournament?->prize_pool ?? 0) }}" style="width:100%;padding:10px;border:1px solid #d9e1e7;border-radius:10px;">
                    </div>
                    <div>
                        <label>Registration Start</label>
                        <input type="datetime-local" name="registration_starts_at" value="{{ old('registration_starts_at', optional($editingTournament?->entry_open_at)->format('Y-m-d\\TH:i')) }}" style="width:100%;padding:10px;border:1px solid #d9e1e7;border-radius:10px;">
                    </div>
                    <div>
                        <label>Registration End</label>
                        <input type="datetime-local" name="registration_ends_at" value="{{ old('registration_ends_at', optional($editingTournament?->entry_close_at)->format('Y-m-d\\TH:i')) }}" style="width:100%;padding:10px;border:1px solid #d9e1e7;border-radius:10px;">
                    </div>
                    <div>
                        <label>Tournament Start</label>
                        <input type="datetime-local" name="starts_at" value="{{ old('starts_at', optional($editingTournament?->start_at)->format('Y-m-d\\TH:i')) }}" style="width:100%;padding:10px;border:1px solid #d9e1e7;border-radius:10px;">
                    </div>
                    <div>
                        <label>Tournament End</label>
                        <input type="datetime-local" name="ends_at" value="{{ old('ends_at', optional($editingTournament?->end_at)->format('Y-m-d\\TH:i')) }}" style="width:100%;padding:10px;border:1px solid #d9e1e7;border-radius:10px;">
                    </div>
                </div>

                <div>
                    <label>Description / Notes</label>
                    <textarea name="metadata[notes]" rows="3" style="width:100%;padding:10px;border:1px solid #d9e1e7;border-radius:10px;">{{ old('metadata.notes', $editingMeta['notes'] ?? '') }}</textarea>
                </div>

                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:16px;">
                    <div>
                        <label>Platform Fee</label>
                        <input type="number" step="0.0001" name="platform_fee" value="{{ old('platform_fee', $editingTournament?->platform_fee ?? 0) }}" style="width:100%;padding:10px;border:1px solid #d9e1e7;border-radius:10px;">
                    </div>
                </div>

                <div>
                    <strong>Prize Slabs</strong>
                    <div class="muted" style="margin:6px 0 12px;">First 3 rows are editable in this first-pass admin screen.</div>
                    @php
                        $prizes = old('prize_slabs', $editingTournament?->prizes?->map(fn($prize) => [
                            'rank_from' => $prize->rank_from,
                            'rank_to' => $prize->rank_to,
                            'prize_type' => $prize->prize_type,
                            'prize_amount' => $prize->prize_amount,
                            'currency' => $prize->currency,
                        ])->values()->all() ?? [['rank_from'=>1,'rank_to'=>1,'prize_type'=>'cash','prize_amount'=>0,'currency'=>'INR']]);
                    @endphp
                    @for ($i = 0; $i < 3; $i++)
                        @php $prize = $prizes[$i] ?? ['rank_from'=>'','rank_to'=>'','prize_type'=>'cash','prize_amount'=>'','currency'=>'INR']; @endphp
                        <div style="display:grid;grid-template-columns:repeat(5,minmax(120px,1fr));gap:12px;margin-bottom:12px;">
                            <input type="number" name="prize_slabs[{{ $i }}][rank_from]" placeholder="Rank From" value="{{ $prize['rank_from'] }}" style="padding:10px;border:1px solid #d9e1e7;border-radius:10px;">
                            <input type="number" name="prize_slabs[{{ $i }}][rank_to]" placeholder="Rank To" value="{{ $prize['rank_to'] }}" style="padding:10px;border:1px solid #d9e1e7;border-radius:10px;">
                            <input name="prize_slabs[{{ $i }}][prize_type]" placeholder="Prize Type" value="{{ $prize['prize_type'] }}" style="padding:10px;border:1px solid #d9e1e7;border-radius:10px;">
                            <input type="number" step="0.0001" name="prize_slabs[{{ $i }}][prize_amount]" placeholder="Prize Amount" value="{{ $prize['prize_amount'] }}" style="padding:10px;border:1px solid #d9e1e7;border-radius:10px;">
                            <input name="prize_slabs[{{ $i }}][currency]" placeholder="Currency" value="{{ $prize['currency'] }}" style="padding:10px;border:1px solid #d9e1e7;border-radius:10px;">
                        </div>
                    @endfor
                </div>

                <div>
                    <button class="btn" type="submit">{{ $isEdit ? 'Update Tournament' : 'Create Tournament' }}</button>
                </div>
            </form>
        </div>

        <div class="panel">
            <div class="header-row">
                <strong>Tournament List</strong>
                <span class="muted">Operational view with quick edit access</span>
            </div>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Game</th>
                            <th>Status</th>
                            <th>Type</th>
                            <th>Entry Fee</th>
                            <th>Entries</th>
                            <th>Starts At</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($tournaments as $tournament)
                            <tr>
                                <td>{{ $tournament->name }}</td>
                                <td>{{ $tournament->game?->name ?: '-' }}</td>
                                <td>{{ $tournament->status }}</td>
                                <td>{{ $tournament->type }}</td>
                                <td>{{ $tournament->entry_fee }} {{ $tournament->currency }}</td>
                                <td>{{ $tournament->entries()->count() }} / {{ $tournament->max_total_entries ?: 'Open' }}</td>
                                <td>{{ optional($tournament->start_at)->toDateTimeString() ?: '-' }}</td>
                                <td><a class="btn btn-secondary" href="{{ route('admin.tournaments.edit', $tournament) }}">Edit</a></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="muted">No tournaments found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div style="margin-top:16px;">{{ $tournaments->links() }}</div>
        </div>
    </div>
@endsection
