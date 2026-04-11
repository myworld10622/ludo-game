@extends('admin.layouts.app')

@section('title', 'Deposit Requests')
@section('heading', 'Deposit Requests')
@section('subheading', 'Distributor to admin requests')

@section('content')
<div class="panel stack">
    @if (! $exists)
        <div class="error-list">Legacy tables for deposits not found.</div>
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
                    <th>Distributor</th>
                    <th>Distributor ID</th>
                    <th>Amount</th>
                    <th>Txn ID</th>
                    <th>Gateway</th>
                    <th>Status</th>
                    <th>Created</th>
                </tr>
                </thead>
                <tbody>
                @forelse ($pending as $row)
                    <tr>
                        <td data-label="ID">{{ $row->id }}</td>
                        <td data-label="Distributor">{{ $row->distributor ?? '-' }}</td>
                        <td data-label="Distributor ID">{{ $row->distributor_id }}</td>
                        <td data-label="Amount">{{ $row->amount }}</td>
                        <td data-label="Txn ID">{{ $row->txn_id }}</td>
                        <td data-label="Gateway">{{ $row->gateway_name }}</td>
                        <td data-label="Status">
                            <select data-deposit-status="{{ $row->id }}">
                                <option value="0" selected>Pending</option>
                                <option value="1">Approve</option>
                                <option value="2">Reject</option>
                            </select>
                        </td>
                        <td data-label="Created">{{ $row->created_date }}</td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="muted">No pending deposits.</td></tr>
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
                    <th>Distributor</th>
                    <th>Distributor ID</th>
                    <th>Amount</th>
                    <th>Txn ID</th>
                    <th>Gateway</th>
                    <th>Status</th>
                    <th>Created</th>
                </tr>
                </thead>
                <tbody>
                @forelse ($approved as $row)
                    <tr>
                        <td data-label="ID">{{ $row->id }}</td>
                        <td data-label="Distributor">{{ $row->distributor ?? '-' }}</td>
                        <td data-label="Distributor ID">{{ $row->distributor_id }}</td>
                        <td data-label="Amount">{{ $row->amount }}</td>
                        <td data-label="Txn ID">{{ $row->txn_id }}</td>
                        <td data-label="Gateway">{{ $row->gateway_name }}</td>
                        <td data-label="Status">
                            <select data-deposit-status="{{ $row->id }}">
                                <option value="1" selected>Approved</option>
                                <option value="2">Reject</option>
                            </select>
                        </td>
                        <td data-label="Created">{{ $row->created_date }}</td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="muted">No approved deposits.</td></tr>
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
                    <th>Distributor</th>
                    <th>Distributor ID</th>
                    <th>Amount</th>
                    <th>Txn ID</th>
                    <th>Gateway</th>
                    <th>Status</th>
                    <th>Created</th>
                </tr>
                </thead>
                <tbody>
                @forelse ($rejected as $row)
                    <tr>
                        <td data-label="ID">{{ $row->id }}</td>
                        <td data-label="Distributor">{{ $row->distributor ?? '-' }}</td>
                        <td data-label="Distributor ID">{{ $row->distributor_id }}</td>
                        <td data-label="Amount">{{ $row->amount }}</td>
                        <td data-label="Txn ID">{{ $row->txn_id }}</td>
                        <td data-label="Gateway">{{ $row->gateway_name }}</td>
                        <td data-label="Status">
                            <select data-deposit-status="{{ $row->id }}">
                                <option value="2" selected>Rejected</option>
                                <option value="1">Approve</option>
                            </select>
                        </td>
                        <td data-label="Created">{{ $row->created_date }}</td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="muted">No rejected deposits.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.querySelectorAll('[data-deposit-status]').forEach(function (select) {
    select.addEventListener('change', function () {
        const id = select.getAttribute('data-deposit-status');
        const status = select.value;
        fetch("{{ route('admin.deposits.status') }}", {
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
</script>
@endpush
@endsection
