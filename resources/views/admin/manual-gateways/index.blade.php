@extends('admin.layouts.app')

@section('title', 'UPI / Bank Gateways')
@section('heading', 'Manual Payment Gateways')
@section('subheading', 'Manage UPI / Bank accounts shown to users')

@section('content')
<div class="panel stack">

    {{-- Payment Option Toggles --}}
    <div class="panel" style="padding:16px 20px;">
        <div style="font-weight:700;font-size:15px;margin-bottom:14px;">Payment Options — Show / Hide in App</div>

        @php
        $options = [
            ['id' => 'globalToggle',  'label' => 'Option 1 — Manual UPI / Bank',  'checked' => $globalEnabled,  'ajax' => route('admin.manual-gateways.toggle-global'),  'field' => null,  'num' => 1],
            ['id' => 'option2Toggle', 'label' => 'Option 2 — Automatic Gateway',  'checked' => $option2Enabled, 'ajax' => route('admin.manual-gateways.toggle-option', 2), 'field' => null,  'num' => 2],
            ['id' => 'option3Toggle', 'label' => 'Option 3 — USDT Manual',        'checked' => $option3Enabled, 'ajax' => route('admin.manual-gateways.toggle-option', 3), 'field' => null,  'num' => 3],
            ['id' => 'option4Toggle', 'label' => 'Option 4 — BEP20 USDT',         'checked' => $option4Enabled, 'ajax' => route('admin.manual-gateways.toggle-option', 4), 'field' => null,  'num' => 4],
        ];
        @endphp

        @foreach($options as $opt)
        <div style="display:flex;align-items:center;gap:16px;margin-bottom:10px;">
            <span style="min-width:240px;font-weight:600;">{{ $opt['label'] }}</span>
            <label class="toggle-switch">
                <input type="checkbox" id="{{ $opt['id'] }}" data-url="{{ $opt['ajax'] }}" data-num="{{ $opt['num'] }}" class="optionToggle" {{ $opt['checked'] ? 'checked' : '' }}>
                <span class="slider"></span>
            </label>
            <span id="{{ $opt['id'] }}Label" style="font-weight:600;color:{{ $opt['checked'] ? '#22c55e' : '#ef4444' }}">
                {{ $opt['checked'] ? 'ON' : 'OFF' }}
            </span>
        </div>
        @endforeach
    </div>

    <div class="panel">
        <div class="header-row">
            <div style="font-weight:800;">Payment Accounts ({{ $gateways->count() }})</div>
            <a class="btn" href="{{ route('admin.manual-gateways.create') }}">+ Add Account</a>
        </div>

        @if(session('success'))
            <div class="success-msg" style="color:#22c55e;padding:8px 0;">{{ session('success') }}</div>
        @endif

        <div class="table-wrap responsive-table">
            <table>
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Type</th>
                    <th>UPI ID / Account</th>
                    <th>QR</th>
                    <th>Active</th>
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody>
                @forelse($gateways as $gw)
                <tr>
                    <td>{{ $gw->id }}</td>
                    <td><strong>{{ $gw->gateway_name }}</strong></td>
                    <td>
                        <span class="badge {{ $gw->type === 'upi' ? 'badge-blue' : 'badge-purple' }}">
                            {{ strtoupper($gw->type) }}
                        </span>
                    </td>
                    <td>
                        @if($gw->type === 'upi')
                            {{ $gw->upi_id ?: '—' }}
                        @else
                            {{ $gw->bank_name }}<br>
                            <small>{{ $gw->account_number }} | {{ $gw->ifsc_code }}</small>
                        @endif
                    </td>
                    <td>
                        @if($gw->qr_image_url)
                            <img src="{{ $gw->qr_image_url }}" style="width:48px;height:48px;object-fit:contain;border:1px solid #ddd;border-radius:4px;">
                        @else
                            <span style="color:#aaa">No QR</span>
                        @endif
                    </td>
                    <td>
                        <label class="toggle-switch">
                            <input type="checkbox" class="activeToggle" data-id="{{ $gw->id }}" {{ $gw->is_active ? 'checked' : '' }}>
                            <span class="slider"></span>
                        </label>
                    </td>
                    <td>
                        <a class="btn btn-sm" href="{{ route('admin.manual-gateways.edit', $gw) }}">Edit</a>
                        <form method="POST" action="{{ route('admin.manual-gateways.destroy', $gw) }}" style="display:inline;" onsubmit="return confirm('Delete this gateway?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-danger" type="submit">Delete</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" style="text-align:center;color:#888;padding:24px;">No gateways added yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <p style="margin-top:12px;color:#888;font-size:13px;">
            💡 If multiple accounts are active, users will see a <strong>random</strong> one each time.
        </p>
    </div>
</div>

<style>
.toggle-switch { position:relative;display:inline-block;width:44px;height:24px; }
.toggle-switch input { opacity:0;width:0;height:0; }
.slider { position:absolute;cursor:pointer;inset:0;background:#ccc;border-radius:24px;transition:.3s; }
.slider:before { position:absolute;content:"";height:18px;width:18px;left:3px;bottom:3px;background:#fff;border-radius:50%;transition:.3s; }
input:checked + .slider { background:#22c55e; }
input:checked + .slider:before { transform:translateX(20px); }
.badge { padding:2px 8px;border-radius:4px;font-size:12px;font-weight:600; }
.badge-blue { background:#dbeafe;color:#1d4ed8; }
.badge-purple { background:#ede9fe;color:#7c3aed; }
.btn-sm { padding:4px 10px;font-size:13px; }
.btn-danger { background:#ef4444;color:#fff;border:none; }
</style>

<script>
// Payment option on/off toggles
document.querySelectorAll('.optionToggle').forEach(function(el) {
    el.addEventListener('change', function() {
        const enabled = this.checked;
        const labelEl = document.getElementById(this.id + 'Label');
        if (labelEl) { labelEl.textContent = enabled ? 'ON' : 'OFF'; labelEl.style.color = enabled ? '#22c55e' : '#ef4444'; }
        fetch(this.dataset.url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            body: JSON.stringify({ enabled })
        });
    });
});

// Individual gateway account active toggles
document.querySelectorAll('.activeToggle').forEach(function(el) {
    el.addEventListener('change', function() {
        const id = this.dataset.id;
        fetch('/admin/manual-gateways/' + id + '/toggle', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
        });
    });
});
</script>
@endsection
