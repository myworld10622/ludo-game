@extends('admin.layouts.app')

@section('title', 'Welcome Bonus')
@section('heading', 'Welcome Bonus')
@section('subheading', 'Legacy welcome rewards and logs')

@section('content')
<div class="panel stack">
    <div>
        <div style="font-weight: 800; margin-bottom: 8px;">Rewards</div>
        <div class="table-wrap responsive-table">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Coin</th>
                        <th>Game Played</th>
                        <th>Added Date</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rewards as $row)
                        <tr>
                            <td data-label="ID">{{ $row->id }}</td>
                            <td data-label="Coin">{{ $row->coin }}</td>
                            <td data-label="Game Played">{{ $row->game_played }}</td>
                            <td data-label="Added">{{ $row->added_date ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="muted">No rewards found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div>
        <form method="GET" class="split-2">
            <div>
                <label>User ID (logs)</label>
                <input type="text" name="user_id" value="{{ $filters['user_id'] ?? '' }}" placeholder="user_id">
            </div>
            <div style="align-self: end;">
                <button class="btn" type="submit">Filter Logs</button>
                <a class="btn btn-secondary" href="{{ route('admin.legacy-reports.welcome-bonus') }}">Reset</a>
            </div>
        </form>

        <div style="font-weight: 800; margin: 12px 0;">Collected Logs</div>
        <div class="table-wrap responsive-table">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>User ID</th>
                        <th>Coin</th>
                        <th>Added Date</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($logs as $row)
                        <tr>
                            <td data-label="ID">{{ $row->id }}</td>
                            <td data-label="User ID">{{ $row->user_id }}</td>
                            <td data-label="Coin">{{ $row->coin }}</td>
                            <td data-label="Added">{{ $row->added_date }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="muted">No logs found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
