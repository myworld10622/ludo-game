@extends('admin.layouts.app')

@section('title', 'Withdrawal Requests')
@section('heading', 'Withdrawal Requests')
@section('subheading', 'Pending requests stay here until you approve, reject, or send them to Betzono')

@section('content')
<style>
    .content:has(.withdrawals-page) {
        overflow-x: auto !important;
    }

    .withdrawals-page {
        overflow: visible;
        width: 100%;
        min-width: 0;
    }

    .withdrawals-page .panel {
        min-width: 0;
    }

    .withdrawals-table-wrap.responsive-table {
        display: block;
        width: 100%;
        max-width: 100%;
        overflow-x: auto !important;
        overflow-y: visible !important;
        padding-bottom: 6px;
        scrollbar-gutter: stable both-edges;
    }

    .withdrawals-table {
        width: 1760px;
        min-width: 1760px;
        table-layout: auto;
    }

    .withdrawals-table th,
    .withdrawals-table td {
        white-space: nowrap;
    }

    .withdrawals-table .wrap-cell,
    .withdrawals-table .trail-cell {
        white-space: normal;
        min-width: 220px;
    }

    .withdrawals-table .user-cell {
        min-width: 190px;
    }

    .withdrawals-table .request-cell {
        min-width: 240px;
    }

    .withdrawals-table .trail-cell {
        min-width: 280px;
    }

    .withdrawals-table .action-cell {
        min-width: 170px;
    }

    .withdrawals-table .narrow-cell {
        min-width: 120px;
    }

    .withdrawals-table .status-cell {
        min-width: 140px;
    }

    .withdrawals-table .trail-preview {
        max-width: 260px;
        white-space: normal;
        word-break: break-word;
    }

    .detail-button {
        min-width: 96px;
        justify-content: center;
    }

    .detail-list {
        display: grid;
        gap: 12px;
    }

    .detail-item {
        display: grid;
        grid-template-columns: 160px 1fr auto;
        gap: 12px;
        align-items: center;
        padding: 12px 14px;
        border: 1px solid var(--line-dim);
        border-radius: 12px;
        background: rgba(255,255,255,0.02);
    }

    .detail-item strong {
        color: var(--gold);
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .detail-item span {
        word-break: break-word;
    }

    .detail-copy {
        min-width: 82px;
        padding: 8px 12px;
    }

    @media (max-width: 1400px) {
        .withdrawals-table {
            width: 1620px;
            min-width: 1620px;
        }
    }

    @media (max-width: 700px) {
        .withdrawals-table-wrap.responsive-table {
            overflow-x: visible !important;
        }

        .withdrawals-table {
            width: 100%;
            min-width: 100%;
        }

        .detail-item {
            grid-template-columns: 1fr;
        }
    }
</style>
<div class="panel stack withdrawals-page">
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
        <div class="table-wrap responsive-table withdrawals-table-wrap">
            <table class="withdrawals-table">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>User</th>
                    <th>Request Details</th>
                    <th>Type</th>
                    <th>Bank Details</th>
                    <th>Crypto Details</th>
                    <th>Coin</th>
                    <th>Trail</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th>Transfer</th>
                </tr>
                </thead>
                <tbody>
                @forelse ($pending as $row)
                    <tr>
                        <td data-label="ID">{{ $row->id }}</td>
                        <td data-label="User" class="wrap-cell user-cell">
                            <div style="font-weight:700;">{{ $row->user_name ?? '-' }}</div>
                            <div class="muted">Legacy ID: {{ $row->user_id }}</div>
                            <div class="muted">Mobile: {{ $row->user_mobile ?? '-' }}</div>
                        </td>
                        <td data-label="Request Details" class="wrap-cell request-cell">
                            <div><strong>Request ID:</strong> {{ $row->id }}</div>
                            <div><strong>Txn:</strong> {{ $row->transaction_id ?: '-' }}</div>
                            <div><strong>Mode:</strong> {{ (int) $row->type === 0 ? 'Bank Withdraw' : 'Crypto Withdraw' }}</div>
                        </td>
                        <td data-label="Type" class="narrow-cell">{{ (int) $row->type === 0 ? 'Bank' : 'Crypto' }}</td>
                        <td data-label="Bank Details" class="action-cell">
                            <button
                                class="btn btn-secondary detail-button"
                                type="button"
                                data-detail-modal="bank"
                                data-user="{{ $row->user_name ?? '-' }}"
                                data-bank-name="{{ $row->bank_name ?? '' }}"
                                data-account-holder="{{ $row->acc_holder_name ?? '' }}"
                                data-account-number="{{ $row->acc_no ?? '' }}"
                                data-ifsc="{{ $row->ifsc_code ?? '' }}"
                                data-upi-id="{{ $row->upi_id ?? '' }}"
                            >
                                View
                            </button>
                        </td>
                        <td data-label="Crypto Details" class="action-cell">
                            <button
                                class="btn btn-secondary detail-button"
                                type="button"
                                data-detail-modal="crypto"
                                data-user="{{ $row->user_name ?? '-' }}"
                                data-wallet-type="{{ $row->crypto_wallet_type ?? '' }}"
                                data-crypto-address="{{ $row->crypto_address ?? '' }}"
                            >
                                View
                            </button>
                        </td>
                        <td data-label="Coin" class="narrow-cell">{{ $row->coin }}</td>
                        <td data-label="Trail" class="trail-cell">
                            <div><strong>Gateway:</strong> {{ $row->payout_response ? 'Sent/Updated' : 'Not Sent' }}</div>
                            <div class="muted trail-preview">
                                {{ $row->payout_response ? \Illuminate\Support\Str::limit($row->payout_response, 140) : '-' }}
                            </div>
                        </td>
                        <td data-label="Status" class="status-cell">
                            <select data-withdraw-status="{{ $row->id }}">
                                <option value="0" selected>Pending</option>
                                <option value="1">Approve</option>
                                <option value="2">Reject</option>
                            </select>
                        </td>
                        <td data-label="Created" class="narrow-cell">{{ $row->created_date }}</td>
                        <td data-label="Transfer" class="action-cell">
                            <button class="btn btn-secondary" type="button" data-transfer-betzono="{{ $row->id }}">
                                Transfer to Betzono
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="11" class="muted">No pending withdrawals.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="panel">
        <div class="header-row">
            <div style="font-weight: 800;">Approved</div>
        </div>
        <div class="table-wrap responsive-table withdrawals-table-wrap">
            <table class="withdrawals-table">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>User</th>
                    <th>Request Details</th>
                    <th>Bank Details</th>
                    <th>Crypto Details</th>
                    <th>Coin</th>
                    <th>Trail</th>
                    <th>Status</th>
                    <th>Created</th>
                </tr>
                </thead>
                <tbody>
                @forelse ($approved as $row)
                    <tr>
                        <td data-label="ID">{{ $row->id }}</td>
                        <td data-label="User" class="wrap-cell user-cell">
                            <div style="font-weight:700;">{{ $row->user_name ?? '-' }}</div>
                            <div class="muted">Legacy ID: {{ $row->user_id }}</div>
                            <div class="muted">Mobile: {{ $row->user_mobile ?? '-' }}</div>
                        </td>
                        <td data-label="Request Details" class="wrap-cell request-cell">
                            <div><strong>Request ID:</strong> {{ $row->id }}</div>
                            <div><strong>Txn:</strong> {{ $row->transaction_id ?: '-' }}</div>
                        </td>
                        <td data-label="Bank Details" class="action-cell">
                            <button
                                class="btn btn-secondary detail-button"
                                type="button"
                                data-detail-modal="bank"
                                data-user="{{ $row->user_name ?? '-' }}"
                                data-bank-name="{{ $row->bank_name ?? '' }}"
                                data-account-holder="{{ $row->acc_holder_name ?? '' }}"
                                data-account-number="{{ $row->acc_no ?? '' }}"
                                data-ifsc="{{ $row->ifsc_code ?? '' }}"
                                data-upi-id="{{ $row->upi_id ?? '' }}"
                            >
                                View
                            </button>
                        </td>
                        <td data-label="Crypto Details" class="action-cell">
                            <button
                                class="btn btn-secondary detail-button"
                                type="button"
                                data-detail-modal="crypto"
                                data-user="{{ $row->user_name ?? '-' }}"
                                data-wallet-type="{{ $row->crypto_wallet_type ?? '' }}"
                                data-crypto-address="{{ $row->crypto_address ?? '' }}"
                            >
                                View
                            </button>
                        </td>
                        <td data-label="Coin" class="narrow-cell">{{ $row->coin }}</td>
                        <td data-label="Trail" class="trail-cell">
                            <div><strong>Gateway:</strong> {{ $row->payout_response ? 'Sent/Updated' : 'Manual' }}</div>
                            <div class="muted trail-preview">
                                {{ $row->payout_response ? \Illuminate\Support\Str::limit($row->payout_response, 140) : '-' }}
                            </div>
                        </td>
                        <td data-label="Status" class="status-cell">
                            <select data-withdraw-status="{{ $row->id }}">
                                <option value="1" selected>Approved</option>
                                <option value="2">Reject</option>
                            </select>
                        </td>
                        <td data-label="Created" class="narrow-cell">{{ $row->created_date }}</td>
                    </tr>
                @empty
                    <tr><td colspan="10" class="muted">No approved withdrawals.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="panel">
        <div class="header-row">
            <div style="font-weight: 800;">Rejected</div>
        </div>
        <div class="table-wrap responsive-table withdrawals-table-wrap">
            <table class="withdrawals-table">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>User</th>
                    <th>Request Details</th>
                    <th>Bank Details</th>
                    <th>Crypto Details</th>
                    <th>Coin</th>
                    <th>Trail</th>
                    <th>Status</th>
                    <th>Created</th>
                </tr>
                </thead>
                <tbody>
                @forelse ($rejected as $row)
                    <tr>
                        <td data-label="ID">{{ $row->id }}</td>
                        <td data-label="User" class="wrap-cell user-cell">
                            <div style="font-weight:700;">{{ $row->user_name ?? '-' }}</div>
                            <div class="muted">Legacy ID: {{ $row->user_id }}</div>
                            <div class="muted">Mobile: {{ $row->user_mobile ?? '-' }}</div>
                        </td>
                        <td data-label="Request Details" class="wrap-cell request-cell">
                            <div><strong>Request ID:</strong> {{ $row->id }}</div>
                            <div><strong>Txn:</strong> {{ $row->transaction_id ?: '-' }}</div>
                        </td>
                        <td data-label="Bank Details" class="action-cell">
                            <button
                                class="btn btn-secondary detail-button"
                                type="button"
                                data-detail-modal="bank"
                                data-user="{{ $row->user_name ?? '-' }}"
                                data-bank-name="{{ $row->bank_name ?? '' }}"
                                data-account-holder="{{ $row->acc_holder_name ?? '' }}"
                                data-account-number="{{ $row->acc_no ?? '' }}"
                                data-ifsc="{{ $row->ifsc_code ?? '' }}"
                                data-upi-id="{{ $row->upi_id ?? '' }}"
                            >
                                View
                            </button>
                        </td>
                        <td data-label="Crypto Details" class="action-cell">
                            <button
                                class="btn btn-secondary detail-button"
                                type="button"
                                data-detail-modal="crypto"
                                data-user="{{ $row->user_name ?? '-' }}"
                                data-wallet-type="{{ $row->crypto_wallet_type ?? '' }}"
                                data-crypto-address="{{ $row->crypto_address ?? '' }}"
                            >
                                View
                            </button>
                        </td>
                        <td data-label="Coin" class="narrow-cell">{{ $row->coin }}</td>
                        <td data-label="Trail" class="trail-cell">
                            <div><strong>Gateway:</strong> {{ $row->payout_response ? 'Sent/Updated' : 'Manual' }}</div>
                            <div class="muted trail-preview">
                                {{ $row->payout_response ? \Illuminate\Support\Str::limit($row->payout_response, 140) : '-' }}
                            </div>
                        </td>
                        <td data-label="Status" class="status-cell">
                            <select data-withdraw-status="{{ $row->id }}">
                                <option value="2" selected>Rejected</option>
                                <option value="1">Approve</option>
                            </select>
                        </td>
                        <td data-label="Created" class="narrow-cell">{{ $row->created_date }}</td>
                    </tr>
                @empty
                    <tr><td colspan="10" class="muted">No rejected withdrawals.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal-shell" id="withdrawDetailModal">
    <div class="modal-backdrop" data-close-detail-modal></div>
    <div class="modal-card" style="width:min(760px, calc(100vw - 32px));">
        <div class="modal-head">
            <div>
                <div class="topbar-heading" id="withdrawDetailModalTitle">Withdrawal Details</div>
                <div class="topbar-sub" id="withdrawDetailModalSubtitle">Review and copy request details</div>
            </div>
            <button type="button" class="modal-close" data-close-detail-modal>×</button>
        </div>
        <div class="detail-list" id="withdrawDetailModalBody"></div>
    </div>
</div>

@push('scripts')
<script>
const withdrawDetailModal = document.getElementById('withdrawDetailModal');
const withdrawDetailModalTitle = document.getElementById('withdrawDetailModalTitle');
const withdrawDetailModalSubtitle = document.getElementById('withdrawDetailModalSubtitle');
const withdrawDetailModalBody = document.getElementById('withdrawDetailModalBody');

function escapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function detailRow(label, value) {
    const safeValue = value && String(value).trim() !== '' ? String(value) : '-';
    const escaped = escapeHtml(safeValue);
    const canCopy = safeValue !== '-';
    return `
        <div class="detail-item">
            <strong>${escapeHtml(label)}</strong>
            <span>${escaped}</span>
            ${canCopy ? `<button type="button" class="btn btn-secondary detail-copy" data-copy-value="${escaped}">Copy</button>` : `<span></span>`}
        </div>
    `;
}

function openWithdrawDetailModal(type, dataset) {
    const user = dataset.user || 'User';
    if (type === 'bank') {
        withdrawDetailModalTitle.textContent = 'Bank Details';
        withdrawDetailModalSubtitle.textContent = `Withdrawal bank details for ${user}`;
        withdrawDetailModalBody.innerHTML = [
            detailRow('Bank Name', dataset.bankName),
            detailRow('Account Holder', dataset.accountHolder),
            detailRow('Account Number', dataset.accountNumber),
            detailRow('IFSC Code', dataset.ifsc),
            detailRow('UPI ID', dataset.upiId),
        ].join('');
    } else {
        withdrawDetailModalTitle.textContent = 'Crypto Details';
        withdrawDetailModalSubtitle.textContent = `Withdrawal crypto details for ${user}`;
        withdrawDetailModalBody.innerHTML = [
            detailRow('Blockchain', dataset.walletType),
            detailRow('Wallet Address', dataset.cryptoAddress),
        ].join('');
    }

    withdrawDetailModal.classList.add('is-open');
}

function closeWithdrawDetailModal() {
    withdrawDetailModal.classList.remove('is-open');
}

document.querySelectorAll('[data-detail-modal]').forEach(function (button) {
    button.addEventListener('click', function () {
        openWithdrawDetailModal(button.getAttribute('data-detail-modal'), button.dataset);
    });
});

document.querySelectorAll('[data-close-detail-modal]').forEach(function (button) {
    button.addEventListener('click', closeWithdrawDetailModal);
});

withdrawDetailModalBody.addEventListener('click', function (event) {
    const copyButton = event.target.closest('[data-copy-value]');
    if (! copyButton) {
        return;
    }

    const value = copyButton.getAttribute('data-copy-value');
    if (! value) {
        return;
    }

    navigator.clipboard.writeText(value)
        .then(function () {
            alert('Copied successfully');
        })
        .catch(function () {
            alert('Copy failed');
        });
});

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
