@extends('admin.layouts.app')

@section('title', 'Rebate History')
@section('heading', 'Rebate History')
@section('subheading', 'Legacy tbl_rebate_income records')

@section('content')
<div class="panel stack">
    <form method="GET" class="split-2">
        <div>
            <label>User ID</label>
            <input type="text" name="user_id" value="{{ $filters['user_id'] ?? '' }}" placeholder="user_id">
        </div>
        <div style="align-self: end;">
            <button class="btn" type="submit">Filter</button>
            <a class="btn btn-secondary" href="{{ route('admin.legacy-reports.rebate-history') }}">Reset</a>
        </div>
    </form>

    @if (! $exists)
        <div class="error-list">Legacy table tbl_rebate_income not found.</div>
    @endif

    <div class="table-wrap responsive-table">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>User ID</th>
                    <th>Coin</th>
                    <th>From Amount</th>
                    <th>Added Date</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($rows as $row)
                    <tr>
                        <td data-label="ID">{{ $row->id }}</td>
                        <td data-label="User ID">{{ $row->user_id }}</td>
                        <td data-label="Coin">{{ $row->coin ?? '-' }}</td>
                        <td data-label="From">{{ $row->amount ?? '-' }}</td>
                        <td data-label="Added">{{ $row->added_date ?? '-' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="muted">No records found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
