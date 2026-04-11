@extends('admin.layouts.app')

@section('title', 'Withdrawal Logs')
@section('heading', 'Withdrawal Logs')
@section('subheading', 'Legacy tbl_withdrawal_log records')

@section('content')
<div class="panel stack">
    <form method="GET" class="split-2">
        <div>
            <label>User ID</label>
            <input type="text" name="user_id" value="{{ $filters['user_id'] ?? '' }}" placeholder="user_id">
        </div>
        <div style="align-self: end;">
            <button class="btn" type="submit">Filter</button>
            <a class="btn btn-secondary" href="{{ route('admin.legacy-reports.withdrawal-logs') }}">Reset</a>
        </div>
    </form>

    @if (! $exists)
        <div class="error-list">Legacy table tbl_withdrawal_log not found.</div>
    @endif

    <div class="table-wrap responsive-table">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>User ID</th>
                    <th>User</th>
                    <th>Coin</th>
                    <th>Type</th>
                    <th>Status</th>
                    <th>Created</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($rows as $row)
                    <tr>
                        <td data-label="ID">{{ $row->id }}</td>
                        <td data-label="User ID">{{ $row->user_id }}</td>
                        <td data-label="User">{{ $row->user_name ?? '-' }}</td>
                        <td data-label="Coin">{{ $row->coin ?? '-' }}</td>
                        <td data-label="Type">{{ $row->type ?? '-' }}</td>
                        <td data-label="Status">{{ $row->status ?? '-' }}</td>
                        <td data-label="Created">{{ $row->created_date ?? '-' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="muted">No records found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
