@extends('admin.layouts.app')

@section('title', 'Games')
@section('heading', 'Games')
@section('subheading', 'Manage game availability, routing, and tournament support')

@section('content')
    <div class="panel">
        <div class="header-row">
            <strong>Game Catalog</strong>
            <span class="muted">Create and update via admin API endpoints.</span>
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
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="muted">No games found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div style="margin-top:16px;">{{ $games->links() }}</div>
    </div>
@endsection
