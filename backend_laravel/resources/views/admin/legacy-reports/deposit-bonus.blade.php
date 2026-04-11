@extends('admin.layouts.app')

@section('title', 'Deposit Bonus')
@section('heading', 'Deposit Bonus')
@section('subheading', 'Legacy tbl_purcharse_ref records')

@section('content')
<div class="panel stack">
    <form method="GET" class="split-2">
        <div>
            <label>User ID</label>
            <input type="text" name="user_id" value="{{ $filters['user_id'] ?? '' }}" placeholder="Legacy user_id">
        </div>
        <div>
            <label>Type</label>
            <input type="text" name="type" value="{{ $filters['type'] ?? '' }}" placeholder="Type">
        </div>
        <div>
            <label>Purchase User ID</label>
            <input type="text" name="purchase_user_id" value="{{ $filters['purchase_user_id'] ?? '' }}" placeholder="purchase_user_id">
        </div>
        <div>
            <label>Date</label>
            <input type="text" name="date" value="{{ $filters['date'] ?? '' }}" placeholder="YYYY-MM-DD">
        </div>
        <div class="mobile-actions" style="grid-column: 1 / -1;">
            <button class="btn" type="submit">Filter</button>
            <a class="btn btn-secondary" href="{{ route('admin.legacy-reports.deposit-bonus') }}">Reset</a>
        </div>
    </form>

    @if (! $exists)
        <div class="error-list">Legacy table tbl_purcharse_ref not found.</div>
    @endif

    <div class="table-wrap responsive-table">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>User ID</th>
                    <th>Purchase User</th>
                    <th>Coin</th>
                    <th>Purchase Amount</th>
                    <th>Type</th>
                    <th>Added Date</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($rows as $row)
                    <tr>
                        <td data-label="ID">{{ $row->id }}</td>
                        <td data-label="User ID">{{ $row->user_id }}</td>
                        <td data-label="Purchase User">{{ $row->purchase_user_id ?? '-' }}</td>
                        <td data-label="Coin">{{ $row->coin ?? '-' }}</td>
                        <td data-label="Amount">{{ $row->purchase_amount ?? '-' }}</td>
                        <td data-label="Type">{{ $row->type ?? '-' }}</td>
                        <td data-label="Added">{{ $row->added_date ?? '-' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="muted">No records found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
