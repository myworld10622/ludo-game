@extends('admin.layouts.app')

@section('title', 'Bet Commission')
@section('heading', 'Bet Commission')
@section('subheading', 'Legacy tbl_bet_income_log records')

@section('content')
<div class="panel stack">
    <form method="GET" class="split-2">
        <div>
            <label>To User ID</label>
            <input type="text" name="user_id" value="{{ $filters['user_id'] ?? '' }}" placeholder="to_user_id">
        </div>
        <div style="align-self: end;">
            <button class="btn" type="submit">Filter</button>
            <a class="btn btn-secondary" href="{{ route('admin.legacy-reports.bet-commission') }}">Reset</a>
        </div>
    </form>

    @if (! $exists)
        <div class="error-list">Legacy table tbl_bet_income_log not found.</div>
    @endif

    <div class="table-wrap responsive-table">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Bet User</th>
                    <th>To User</th>
                    <th>Bonus</th>
                    <th>Name</th>
                    <th>Added Date</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($rows as $row)
                    <tr>
                        <td data-label="ID">{{ $row->id }}</td>
                        <td data-label="Bet User">{{ $row->bet_user_id ?? '-' }}</td>
                        <td data-label="To User">{{ $row->to_user_id ?? '-' }}</td>
                        <td data-label="Bonus">{{ $row->bonus ?? '-' }}</td>
                        <td data-label="Name">{{ $row->name ?? '-' }}</td>
                        <td data-label="Added">{{ $row->added_date ?? '-' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="muted">No records found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
