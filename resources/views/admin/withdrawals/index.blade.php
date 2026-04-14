@extends('admin.layouts.app')

@section('title', 'Withdrawal Requests')
@section('heading', 'Withdrawal Requests')
@section('subheading', 'Approve or reject legacy withdrawal logs')

@section('content')
<div class="panel stack">
    <form method="GET" class="split-2">
        <div>
            <label>Start Date</label>
            <input type="text" name="start_date" value="{{ $filters['start_date'] ?? '' }}" placeholder="YYYY-MM-DD">
        </div>
        <div>
            <label>End Date</label>
            <input type="text" name="end_date" value="{{ $filters['end_date'] ?? '' }}" placeholder="YYYY-MM-DD">
        </div>
        <div class="mobile-actions" style="grid-column: 1 / -1;">
            <button class="btn" type="submit">Filter</button>
            <a class="btn btn-secondary" href="{{ route('admin.withdrawals.index') }}">Reset</a>
        </div>
    </form>

    @if (! $exists)
        <div class="error-list">Legacy table tbl_withdrawal_log not found.</div>
    @endif

    <div class="panel">
        <div class="header-row">
            <div style="font-weight: 800;">Pending</div>
        </div>
        <div class="table-wrap responsive-table">
            <table>
                <thead>
                <tr>
                    <th>ID</th>
                    <th>User</th>
                    <th>User ID</th>
                    <th>Type</th>
                    <th>Bank</th>
                    <th>IFSC</th>
                    <th>Account</th>
                    <th>Crypto Address</th>
                    <th>Wallet Type</th>
                    <th>Coin</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th>Transfer</th>
                </tr>
                </thead>
                <tbody>
                @forelse ($pending as $row)
                    <tr>
                        <td data-label="ID">{{ $row->id }}</td>
                        <td data-label="User">{{ $row->user_name ?? '-' }}</td>
                        <td data-label="User ID">{{ $row->user_id }}</td>
                        <td data-label="Type">{{ (int) $row->type === 0 ? 'Bank' : 'Crypto' }}</td>
                        <td data-label="Bank">{{ $row->bank_name }}</td>
                        <td data-label="IFSC">{{ $row->ifsc_code }}</td>
                        <td data-label="Account">{{ $row->acc_no }}</td>
                        <td data-label="Crypto">{{ $row->crypto_address }}</td>
                        <td data-label="Wallet">{{ $row->crypto_wallet_type }}</td>
                        <td data-label="Coin">{{ $row->coin }}</td>
                        <td data-label="Status">
                            <select data-withdraw-status="{{ $row->id }}">
                                <option value="0" selected>Pending</option>
                                <option value="1">Approve</option>
                                <option value="2">Reject</option>
                            </select>
                        </td>
                        <td data-label="Created">{{ $row->created_date }}</td>
                        <td data-label="Transfer">
                            <button class="btn btn-secondary" type="button" data-transfer-betzono="{{ $row->id }}">
                                Send to Betzono
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="13" class="muted">No pending withdrawals.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="panel">
        <div class="header-row">
            <div style="font-weight: 800;">Approved</div>
        </div>
        <div class="table-wrap responsive-table">
            <table>
                <thead>
                <tr>
                    <th>ID</th>
                    <th>User</th>
                    <th>User ID</th>
                    <th>Bank</th>
                    <th>IFSC</th>
                    <th>Account</th>
                    <th>Crypto Address</th>
                    <th>Wallet Type</th>
                    <th>Coin</th>
                    <th>Status</th>
                    <th>Created</th>
                </tr>
                </thead>
                <tbody>
                @forelse ($approved as $row)
                    <tr>
                        <td data-label="ID">{{ $row->id }}</td>
                        <td data-label="User">{{ $row->user_name ?? '-' }}</td>
                        <td data-label="User ID">{{ $row->user_id }}</td>
                        <td data-label="Bank">{{ $row->bank_name }}</td>
                        <td data-label="IFSC">{{ $row->ifsc_code }}</td>
                        <td data-label="Account">{{ $row->acc_no }}</td>
                        <td data-label="Crypto">{{ $row->crypto_address }}</td>
                        <td data-label="Wallet">{{ $row->crypto_wallet_type }}</td>
                        <td data-label="Coin">{{ $row->coin }}</td>
                        <td data-label="Status">
                            <select data-withdraw-status="{{ $row->id }}">
                                <option value="1" selected>Approved</option>
                                <option value="2">Reject</option>
                            </select>
                        </td>
                        <td data-label="Created">{{ $row->created_date }}</td>
                    </tr>
                @empty
                    <tr><td colspan="11" class="muted">No approved withdrawals.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="panel">
        <div class="header-row">
            <div style="font-weight: 800;">Rejected</div>
        </div>
        <div class="table-wrap responsive-table">
            <table>
                <thead>
                <tr>
                    <th>ID</th>
                    <th>User</th>
                    <th>User ID</th>
                    <th>Bank</th>
                    <th>IFSC</th>
                    <th>Account</th>
                    <th>Crypto Address</th>
                    <th>Wallet Type</th>
                    <th>Coin</th>
                    <th>Status</th>
                    <th>Created</th>
                </tr>
                </thead>
                <tbody>
                @forelse ($rejected as $row)
                    <tr>
                        <td data-label="ID">{{ $row->id }}</td>
                        <td data-label="User">{{ $row->user_name ?? '-' }}</td>
                        <td data-label="User ID">{{ $row->user_id }}</td>
                        <td data-label="Bank">{{ $row->bank_name }}</td>
                        <td data-label="IFSC">{{ $row->ifsc_code }}</td>
                        <td data-label="Account">{{ $row->acc_no }}</td>
                        <td data-label="Crypto">{{ $row->crypto_address }}</td>
                        <td data-label="Wallet">{{ $row->crypto_wallet_type }}</td>
                        <td data-label="Coin">{{ $row->coin }}</td>
                        <td data-label="Status">
                            <select data-withdraw-status="{{ $row->id }}">
                                <option value="2" selected>Rejected</option>
                                <option value="1">Approve</option>
                            </select>
                        </td>
                        <td data-label="Created">{{ $row->created_date }}</td>
                    </tr>
                @empty
                    <tr><td colspan="11" class="muted">No rejected withdrawals.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.querySelectorAll('[data-withdraw-status]').forEach(function (select) {
    select.addEventListener('change', function () {
        const id = select.getAttribute('data-withdraw-status');
        const status = select.value;
        fetch("{{ route('admin.withdrawals.status') }}", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": "{{ csrf_token() }}"
            },
            body: JSON.stringify({ id, status })
        })
        .then(function (res) { return res.json(); })
        .then(function (data) {
            if (data.class === 'success') {
                alert(data.msg);
                window.location.reload();
            } else {
                alert(data.msg || 'Something went to wrong');
            }
        })
        .catch(function () {
            alert('Status update failed');
        });
    });
});

document.querySelectorAll('[data-transfer-betzono]').forEach(function (button) {
    button.addEventListener('click', function () {
        const id = button.getAttribute('data-transfer-betzono');
        if (! id) {
            return;
        }
        if (! confirm('Send this withdrawal to Betzono?')) {
            return;
        }

        fetch("{{ route('admin.withdrawals.transfer') }}", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": "{{ csrf_token() }}"
            },
            body: JSON.stringify({ id })
        })
        .then(function (res) { return res.json(); })
        .then(function (data) {
            if (data.class === 'success') {
                alert(data.msg);
                window.location.reload();
            } else {
                alert(data.msg || 'Transfer failed');
            }
        })
        .catch(function () {
            alert('Transfer request failed');
        });
    });
});
</script>
@endpush
@endsection
