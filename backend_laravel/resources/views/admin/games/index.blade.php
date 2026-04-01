@extends('admin.layouts.app')

@section('title', 'Games')
@section('heading', 'Games')
@section('subheading', 'Manage game availability, routing, and tournament support')

@section('content')
    <div class="panel" style="margin-bottom:16px;">
        <div class="header-row">
            <div>
                <strong>Classic Ludo Fee Tables</strong>
                <div class="muted" style="margin-top:4px;">2 Player aur 4 Player classic lobby ke fee cards, active rooms, aur monitoring ko yahan se manage karo.</div>
            </div>
            <a href="{{ route('admin.games.ludo-tables.index') }}" class="btn">Open Classic Ludo Tables</a>
        </div>
    </div>

    <div class="panel">
        <div class="header-row">
            <strong>Game Catalog</strong>
            <span class="muted">Quick control visibility, activity, and tournament availability from here.</span>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Game</th>
                        <th>Visibility</th>
                        <th>Activity</th>
                        <th>Tournaments</th>
                        <th>Client Route</th>
                        <th>Socket Namespace</th>
                        <th>Sort</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($games as $game)
                        <tr>
                            <td>
                                <strong>{{ $game->name }}</strong><br>
                                <span class="muted">{{ $game->code }}</span>
                            </td>
                            <td><span class="badge {{ $game->is_visible ? '' : 'off' }}">{{ $game->is_visible ? 'Visible' : 'Hidden' }}</span></td>
                            <td><span class="badge {{ $game->is_active ? '' : 'off' }}">{{ $game->is_active ? 'Active' : 'Disabled' }}</span></td>
                            <td><span class="badge {{ $game->tournaments_enabled ? '' : 'off' }}">{{ $game->tournaments_enabled ? 'Enabled' : 'Off' }}</span></td>
                            <td>{{ $game->client_route ?: '-' }}</td>
                            <td>{{ $game->socket_namespace ?: '-' }}</td>
                            <td>{{ $game->sort_order }}</td>
                            <td style="min-width:280px;">
                                <form method="POST" action="{{ route('admin.games.update', $game) }}" class="stack" style="gap:10px;">
                                    @csrf
                                    <input type="hidden" name="is_visible" value="{{ $game->is_visible ? 0 : 1 }}">
                                    <input type="hidden" name="is_active" value="{{ $game->is_active ? 1 : 0 }}">
                                    <input type="hidden" name="tournaments_enabled" value="{{ $game->tournaments_enabled ? 1 : 0 }}">
                                    <button type="submit" class="btn {{ $game->is_visible ? 'btn-secondary' : '' }}">
                                        {{ $game->is_visible ? 'Hide In Lobby' : 'Show In Lobby' }}
                                    </button>
                                </form>
                                <div class="mobile-actions" style="margin-top:8px;">
                                    <form method="POST" action="{{ route('admin.games.update', $game) }}" style="flex:1;">
                                        @csrf
                                        <input type="hidden" name="is_visible" value="{{ $game->is_visible ? 1 : 0 }}">
                                        <input type="hidden" name="is_active" value="{{ $game->is_active ? 0 : 1 }}">
                                        <input type="hidden" name="tournaments_enabled" value="{{ $game->tournaments_enabled ? 1 : 0 }}">
                                        <button type="submit" class="btn {{ $game->is_active ? 'btn-secondary' : '' }}" style="width:100%;">
                                            {{ $game->is_active ? 'Disable Game' : 'Enable Game' }}
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.games.update', $game) }}" style="flex:1;">
                                        @csrf
                                        <input type="hidden" name="is_visible" value="{{ $game->is_visible ? 1 : 0 }}">
                                        <input type="hidden" name="is_active" value="{{ $game->is_active ? 1 : 0 }}">
                                        <input type="hidden" name="tournaments_enabled" value="{{ $game->tournaments_enabled ? 0 : 1 }}">
                                        <button type="submit" class="btn {{ $game->tournaments_enabled ? 'btn-secondary' : '' }}" style="width:100%;">
                                            {{ $game->tournaments_enabled ? 'Tournament Off' : 'Tournament On' }}
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="muted">No games found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div style="margin-top:16px;">{{ $games->links() }}</div>
    </div>
@endsection
