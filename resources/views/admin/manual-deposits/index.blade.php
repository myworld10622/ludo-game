@extends('admin.layouts.app')

@section('title', 'Manual Deposit Requests')
@section('heading', 'Manual Deposit Requests')
@section('subheading', 'User UPI / Bank manual deposit requests — approve or reject each one')

@section('content')
<style>
    .dep-wrap { overflow-x: auto; }
    .dep-table { width: 100%; border-collapse: collapse; font-size: 13px; min-width: 900px; }
    .dep-table th { padding: 10px 12px; background: #2c3e50; color: #fff; text-align: left; white-space: nowrap; border: 1px solid #3d5166; }
    .dep-table td { padding: 9px 12px; border: 1px solid #dee2e6; white-space: nowrap; color: #212529 !important; background: #ffffff; }
    .dep-table tbody tr:nth-child(odd)  td { background: #f8f9fa; color: #212529 !important; }
    .dep-table tbody tr:nth-child(even) td { background: #ffffff; color: #212529 !important; }
    .dep-table tbody tr:hover td { background: #e8f4fd !important; color: #212529 !important; }

    .tab-bar { display: flex; gap: 6px; margin-bottom: 0; flex-wrap: wrap; }
    .tab-btn { padding: 9px 22px; border: 2px solid transparent; border-bottom: none; border-radius: 6px 6px 0 0; cursor: pointer; font-weight: 700; font-size: 13px; transition: all .15s; }
    .tab-btn.active  { background: #007bff; color: #fff; border-color: #007bff; }
    .tab-btn:not(.active) { background: #e9ecef; color: #495057; border-color: #dee2e6; }
    .tab-btn:not(.active):hover { background: #d5dbe2; }

    .tab-section { display: none; border: 2px solid #dee2e6; border-radius: 0 6px 6px 6px; padding: 16px; background: #fff; }
    .tab-section.active { display: block; }

    .badge-pending  { display:inline-block; background:#ffc107; color:#000; padding:3px 10px; border-radius:12px; font-size:12px; font-weight:600; }
    .badge-approved { display:inline-block; background:#28a745; color:#fff; padding:3px 10px; border-radius:12px; font-size:12px; font-weight:600; }
    .badge-rejected { display:inline-block; background:#dc3545; color:#fff; padding:3px 10px; border-radius:12px; font-size:12px; font-weight:600; }

    .ss-img { max-width: 60px; max-height: 40px; cursor: pointer; border-radius: 4px; border: 1px solid #ccc; object-fit: cover; }
    .action-btn { padding: 5px 12px; border: none; border-radius: 4px; cursor: pointer; font-size: 12px; font-weight: 600; margin: 2px; transition: opacity .15s; }
    .action-btn:hover { opacity: .85; }
    .btn-approve { background:#28a745; color:#fff; }
    .btn-reject  { background:#dc3545; color:#fff; }
    .btn-pending { background:#ffc107; color:#000; }

    .search-bar { display:flex; gap:8px; margin-bottom:16px; align-items:center; flex-wrap:wrap; }
    .search-bar input { padding:7px 12px; border:1px solid #ccc; border-radius:5px; width:280px; font-size:13px; color:#212529; background:#fff; }
    .search-bar .s-btn { padding:7px 18px; background:#007bff; color:#fff; border:none; border-radius:5px; cursor:pointer; font-weight:600; }
    .search-bar .c-btn { padding:7px 14px; background:#6c757d; color:#fff; border-radius:5px; text-decoration:none; font-size:13px; font-weight:600; }

    .ss-modal { display:none; position:fixed; inset:0; background:rgba(0,0,0,0.82); z-index:9999; align-items:center; justify-content:center; }
    .ss-modal.open { display:flex; }
    .ss-modal img { max-width:90vw; max-height:90vh; border-radius:10px; box-shadow:0 4px 32px rgba(0,0,0,.6); }
    .ss-modal .close-btn { position:absolute; top:16px; right:24px; font-size:32px; color:#fff; cursor:pointer; line-height:1; }

    .empty-msg { color:#888; padding:28px 0; text-align:center; font-size:14px; }
    .count-badge { display:inline-block; background:rgba(0,0,0,.18); border-radius:10px; padding:1px 7px; font-size:11px; margin-left:4px; }
</style>

<div class="panel">
    <div class="panel-body">

        @if(isset($missing) && $missing)
            <div class="alert alert-warning">tbl_purchase table not found. No manual deposits recorded yet.</div>
        @else

        {{-- Search --}}
        <form method="GET" action="{{ route('admin.manual-deposits.index') }}" class="search-bar">
            <input type="text" name="search" value="{{ $search ?? '' }}" placeholder="Search username, mobile, UTR...">
            <input type="hidden" name="tab" value="{{ $tab }}">
            <button type="submit" class="s-btn">🔍 Search</button>
            @if($search)
                <a href="{{ route('admin.manual-deposits.index') }}" class="c-btn">✕ Clear</a>
            @endif
        </form>

        {{-- Tabs --}}
        <div class="tab-bar">
            <button class="tab-btn {{ $tab === 'pending'  ? 'active' : '' }}" onclick="switchTab('pending',this)">
                Pending <span class="count-badge">{{ $pending->count() }}</span>
            </button>
            <button class="tab-btn {{ $tab === 'approved' ? 'active' : '' }}" onclick="switchTab('approved',this)">
                Approved <span class="count-badge">{{ $approved->count() }}</span>
            </button>
            <button class="tab-btn {{ $tab === 'rejected' ? 'active' : '' }}" onclick="switchTab('rejected',this)">
                Rejected <span class="count-badge">{{ $rejected->count() }}</span>
            </button>
        </div>

        @foreach(['pending' => $pending, 'approved' => $approved, 'rejected' => $rejected] as $tabKey => $rows)
        <div id="tab-{{ $tabKey }}" class="tab-section {{ $tab === $tabKey ? 'active' : '' }}" style="border:1px solid #dee2e6; border-top:none; padding:16px;">
            @if($rows->isEmpty())
                <p class="empty-msg">📭 No {{ $tabKey }} deposits found.</p>
            @else
            <div class="dep-wrap">
            <table class="dep-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>ID</th>
                        <th>User</th>
                        <th>Mobile</th>
                        <th>Amount (₹)</th>
                        <th>Coins</th>
                        <th>UTR / Txn ID</th>
                        <th>Screenshot</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($rows as $i => $row)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td>{{ $row->id }}</td>
                        <td>{{ $row->user_name ?? 'ID:'.$row->user_id }}</td>
                        <td>{{ $row->user_mobile ?? '-' }}</td>
                        <td>{{ number_format($row->price ?? 0, 2) }}</td>
                        <td>{{ $row->coin ?? '-' }}</td>
                        <td style="max-width:160px; overflow:hidden; text-overflow:ellipsis;" title="{{ $row->utr }}">
                            {{ $row->utr ?: ($row->transaction_id ?: '-') }}
                        </td>
                        <td>
                            @if($row->photo)
                                <img src="{{ $row->photo }}" class="ss-img" onclick="openSS('{{ $row->photo }}')" title="Click to enlarge">
                            @else
                                <span style="color:#aaa;">No image</span>
                            @endif
                        </td>
                        <td>
                            @if((int)$row->status === 0)
                                <span class="badge-pending">Pending</span>
                            @elseif((int)$row->status === 1)
                                <span class="badge-approved">Approved</span>
                            @else
                                <span class="badge-rejected">Rejected</span>
                            @endif
                        </td>
                        <td>{{ $row->added_date ?? '-' }}</td>
                        <td>
                            @if((int)$row->status !== 1)
                                <button class="action-btn btn-approve" onclick="changeStatus({{ $row->id }}, 1, this)">✓ Approve</button>
                            @endif
                            @if((int)$row->status !== 2)
                                <button class="action-btn btn-reject" onclick="changeStatus({{ $row->id }}, 2, this)">✗ Reject</button>
                            @endif
                            @if((int)$row->status !== 0)
                                <button class="action-btn btn-pending" onclick="changeStatus({{ $row->id }}, 0, this)">↺ Pending</button>
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            </div>
            @endif
        </div>
        @endforeach

        @endif
    </div>
</div>

{{-- Screenshot modal --}}
<div class="ss-modal" id="ssModal" onclick="closeSS()">
    <span class="close-btn" onclick="closeSS()">×</span>
    <img id="ssModalImg" src="" alt="Payment Screenshot">
</div>

<script>
function switchTab(name, btn) {
    document.querySelectorAll('.tab-section').forEach(s => s.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.getElementById('tab-' + name).classList.add('active');
    btn.classList.add('active');
}

function changeStatus(id, status, btn) {
    const labels = {0: 'Pending', 1: 'Approve', 2: 'Reject'};
    if (!confirm('Mark this deposit as ' + labels[status] + '?')) return;

    btn.disabled = true;
    btn.textContent = 'Wait...';

    fetch('{{ route("admin.manual-deposits.status") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
        },
        body: JSON.stringify({ id, status }),
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            location.reload();
        } else {
            alert('Error: ' + data.message);
            btn.disabled = false;
            btn.textContent = labels[status];
        }
    })
    .catch(() => { alert('Network error.'); btn.disabled = false; });
}

function openSS(url) { event.stopPropagation(); document.getElementById('ssModalImg').src = url; document.getElementById('ssModal').classList.add('open'); }
function closeSS() { document.getElementById('ssModal').classList.remove('open'); }
</script>
@endsection
