@extends('admin.layouts.app')

@section('title', 'Wallet Transfers')
@section('heading', 'Wallet Transfers')
@section('subheading', 'User to user chips transfer report')

@section('content')
    <div class="panel">
        <form method="GET" class="toolbar" style="margin-bottom:16px;">
            <input type="text" name="search" value="{{ $search }}" placeholder="Transfer ID / Username / User ID / Mobile / Email" style="min-width:320px;">
            <button type="submit" class="btn btn-primary">Search</button>
            @if ($search !== '')
                <a href="{{ route('admin.wallet-transfers.index') }}" class="btn btn-secondary">Reset</a>
            @endif
        </form>

        <div class="table-wrap responsive-table">
            <table>
                <thead>
                    <tr>
                        <th>Transfer</th>
                        <th>Sender</th>
                        <th>Receiver</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Processed</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($transfers as $transfer)
                        <tr>
                            <td data-label="Transfer">
                                <div><strong>{{ $transfer->transfer_uuid }}</strong></div>
                                <div class="muted">Debit Tx: {{ $transfer->senderTransaction?->transaction_uuid ?: '-' }}</div>
                                <div class="muted">Credit Tx: {{ $transfer->receiverTransaction?->transaction_uuid ?: '-' }}</div>
                            </td>
                            <td data-label="Sender">
                                <div>{{ $transfer->sender?->username ?: '-' }}</div>
                                <div class="muted">ID: {{ $transfer->sender?->user_code ?: '-' }}</div>
                            </td>
                            <td data-label="Receiver">
                                <div>{{ $transfer->receiver?->username ?: '-' }}</div>
                                <div class="muted">ID: {{ $transfer->receiver?->user_code ?: '-' }}</div>
                            </td>
                            <td data-label="Amount">{{ $transfer->amount }} {{ $transfer->currency }}</td>
                            <td data-label="Status">{{ ucfirst($transfer->status) }}</td>
                            <td data-label="Processed">
                                <div>{{ optional($transfer->processed_at ?: $transfer->created_at)->toDateTimeString() }}</div>
                                @if ($transfer->note)
                                    <div class="muted">{{ $transfer->note }}</div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="muted">No wallet transfers found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div style="margin-top:16px;">{{ $transfers->links() }}</div>
    </div>
@endsection
