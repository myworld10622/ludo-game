@extends('admin.layouts.app')

@section('title', 'Purchase History')
@section('heading', 'Purchase History')
@section('subheading', 'Legacy tbl_purchase records')

@section('content')
<div class="panel stack">
    <form method="GET" class="split-2">
        <div>
            <label>User ID</label>
            <input type="text" name="user_id" value="{{ $filters['user_id'] ?? '' }}" placeholder="Legacy user_id">
        </div>
        <div style="align-self: end;">
            <button class="btn" type="submit">Filter</button>
            <a class="btn btn-secondary" href="{{ route('admin.legacy-reports.purchase-history') }}">Reset</a>
        </div>
    </form>

    @if (! $exists)
        <div class="error-list">Legacy table tbl_purchase not found.</div>
    @endif

    <div class="table-wrap responsive-table">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>User ID</th>
                    <th>Coin</th>
                    <th>Price</th>
                    <th>Payment</th>
                    <th>Type</th>
                    <th>Added Date</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($rows as $row)
                    <tr>
                        <td data-label="ID">{{ $row->id }}</td>
                        <td data-label="User ID">{{ $row->user_id }}</td>
                        <td data-label="Coin">{{ $row->coin ?? '-' }}</td>
                        <td data-label="Price">{{ $row->price ?? '-' }}</td>
                        <td data-label="Payment">{{ $row->payment ?? '-' }}</td>
                        <td data-label="Type">{{ $row->transaction_type ?? '-' }}</td>
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
