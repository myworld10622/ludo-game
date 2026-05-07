@extends('admin.layouts.app')

@section('title', 'Gateway Transactions')
@section('heading', 'Gateway Transactions')
@section('subheading', 'Rox hosted deposit flow and manual settlement control')

@section('content')
<div class="panel stack">
    <form method="GET" class="split-3">
        <div>
            <label>Status</label>
            <select name="status">
                <option value="">All</option>
                @foreach (['pending', 'success', 'rejected'] as $status)
                    <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ ucfirst($status) }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label>TRX</label>
            <input type="text" name="trx" value="{{ $filters['trx'] ?? '' }}" placeholder="ROX-...">
        </div>
        <div>
            <label>User ID</label>
            <input type="text" name="user_id" value="{{ $filters['user_id'] ?? '' }}" placeholder="App user ID">
        </div>
        <div>
            <label>TRA ID</label>
            <input type="text" name="tra_id" value="{{ $filters['tra_id'] ?? '' }}" placeholder="Provider TRA ID">
        </div>
        <div>
            <label>UTR ID</label>
            <input type="text" name="utr_id" value="{{ $filters['utr_id'] ?? '' }}" placeholder="Bank UTR ID">
        </div>
        <div style="align-self:end;">
            <button class="btn" type="submit">Filter</button>
            <a class="btn btn-secondary" href="{{ route('admin.gateway-transactions.index') }}">Reset</a>
        </div>
    </form>

    @if (! $exists)
        <div class="error-list">Table rox_gateway_transactions not found. Run the latest migrations first.</div>
    @endif

    <div class="table-wrap responsive-table">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>User</th>
                    <th>TRX</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Gateway</th>
                    <th>TRA / UTR</th>
                    <th>Hosted URL</th>
                    <th>Created</th>
                    <th>Manual Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($rows as $row)
                    <tr>
                        <td data-label="ID">{{ $row->id }}</td>
                        <td data-label="User">
                            <div>{{ $row->user_id ?: '-' }}</div>
                            <div class="muted">{{ $row->app_username ?: '-' }}</div>
                        </td>
                        <td data-label="TRX">{{ $row->trx }}</td>
                        <td data-label="Amount">{{ $row->amount }} {{ $row->currency }}</td>
                        <td data-label="Status">
                            <div>{{ ucfirst($row->status) }}</div>
                            <div class="muted">{{ $row->gateway_status ?: '-' }}</div>
                        </td>
                        <td data-label="Gateway">{{ $row->gateway_transaction_id ?: '-' }}</td>
                        <td data-label="TRA / UTR">
                            <div>TRA: {{ $row->tra_id ?: '-' }}</div>
                            <div>UTR: {{ $row->utr_id ?: '-' }}</div>
                        </td>
                        <td data-label="Hosted URL">
                            @if (! empty($row->payment_url))
                                <a href="{{ $row->payment_url }}" target="_blank" rel="noopener">Open</a>
                            @else
                                -
                            @endif
                        </td>
                        <td data-label="Created">{{ $row->created_at ?: '-' }}</td>
                        <td data-label="Manual Action" style="min-width: 280px;">
                            <form method="POST" action="{{ route('admin.gateway-transactions.status') }}" class="stack" style="gap:8px;">
                                @csrf
                                <input type="hidden" name="id" value="{{ $row->id }}">
                                <select name="status">
                                    <option value="pending" @selected($row->status === 'pending')>Pending</option>
                                    <option value="success" @selected($row->status === 'success')>Approve</option>
                                    <option value="rejected" @selected($row->status === 'rejected')>Reject</option>
                                </select>
                                <input type="text" name="tra_id" value="{{ $row->tra_id ?: '' }}" placeholder="TRA ID">
                                <input type="text" name="utr_id" value="{{ $row->utr_id ?: '' }}" placeholder="UTR ID">
                                <textarea name="note" rows="2" placeholder="Manual note">{{ $row->manual_status_note ?? '' }}</textarea>
                                <button type="submit" class="btn">Save</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="10" class="muted">No records found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
