@extends('admin.layouts.app')

@section('title', 'Wallet Transactions')
@section('heading', 'Wallet Transactions')
@section('subheading', 'Operational ledger view for wallet activity')

@section('content')
    <div class="panel">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Txn</th>
                        <th>User</th>
                        <th>Type</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Created</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($transactions as $transaction)
                        <tr>
                            <td>{{ $transaction->transaction_uuid }}</td>
                            <td>{{ $transaction->user?->username ?: '-' }}</td>
                            <td>{{ $transaction->type }}</td>
                            <td>{{ $transaction->amount }} {{ $transaction->currency }}</td>
                            <td>{{ $transaction->status }}</td>
                            <td>{{ optional($transaction->created_at)->toDateTimeString() }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="muted">No wallet transactions found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div style="margin-top:16px;">{{ $transactions->links() }}</div>
    </div>
@endsection
