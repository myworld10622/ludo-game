@extends('admin.layouts.app')

@section('title', $gateway ? 'Edit Gateway' : 'Add Gateway')
@section('heading', $gateway ? 'Edit Payment Account' : 'Add Payment Account')
@section('subheading', 'UPI or Bank Transfer account details shown to users')

@section('content')
<div class="panel" style="max-width:600px;">
    <form method="POST"
          action="{{ $gateway ? route('admin.manual-gateways.update', $gateway) : route('admin.manual-gateways.store') }}"
          enctype="multipart/form-data">
        @csrf
        @if($gateway) @method('PUT') @endif

        @if($errors->any())
            <div style="color:#ef4444;margin-bottom:12px;">
                @foreach($errors->all() as $e) <div>{{ $e }}</div> @endforeach
            </div>
        @endif

        {{-- Gateway Name --}}
        <div class="form-group">
            <label>Gateway Name <span style="color:red">*</span></label>
            <input type="text" name="gateway_name" class="form-control"
                   value="{{ old('gateway_name', $gateway?->gateway_name) }}"
                   placeholder="e.g. PhonePe UPI, SBI Bank Transfer">
        </div>

        {{-- Type --}}
        <div class="form-group">
            <label>Type <span style="color:red">*</span></label>
            <select name="type" id="typeSelect" class="form-control" onchange="toggleFields()">
                <option value="upi" {{ old('type', $gateway?->type) === 'upi' ? 'selected' : '' }}>UPI</option>
                <option value="bank" {{ old('type', $gateway?->type) === 'bank' ? 'selected' : '' }}>Bank Transfer</option>
            </select>
        </div>

        {{-- UPI Fields --}}
        <div id="upiFields">
            <div class="form-group">
                <label>UPI ID</label>
                <input type="text" name="upi_id" class="form-control"
                       value="{{ old('upi_id', $gateway?->upi_id) }}"
                       placeholder="yourname@upi">
            </div>
        </div>

        {{-- Bank Fields --}}
        <div id="bankFields">
            <div class="form-group">
                <label>Bank Name</label>
                <input type="text" name="bank_name" class="form-control"
                       value="{{ old('bank_name', $gateway?->bank_name) }}"
                       placeholder="State Bank of India">
            </div>
            <div class="form-group">
                <label>Account Holder Name</label>
                <input type="text" name="account_holder" class="form-control"
                       value="{{ old('account_holder', $gateway?->account_holder) }}"
                       placeholder="Full name on account">
            </div>
            <div class="form-group">
                <label>Account Number</label>
                <input type="text" name="account_number" class="form-control"
                       value="{{ old('account_number', $gateway?->account_number) }}"
                       placeholder="1234567890">
            </div>
            <div class="form-group">
                <label>IFSC Code</label>
                <input type="text" name="ifsc_code" class="form-control"
                       value="{{ old('ifsc_code', $gateway?->ifsc_code) }}"
                       placeholder="SBIN0001234">
            </div>
        </div>

        {{-- QR Code --}}
        <div class="form-group">
            <label>QR Code Image</label>
            @if($gateway?->qr_image_url)
                <div style="margin-bottom:8px;">
                    <img src="{{ $gateway->qr_image_url }}" style="width:120px;height:120px;object-fit:contain;border:1px solid #ddd;border-radius:8px;">
                    <div style="font-size:12px;color:#888;margin-top:4px;">Upload new to replace</div>
                </div>
            @endif
            <input type="file" name="qr_image" accept="image/*" class="form-control">
        </div>

        {{-- Active Status --}}
        <div class="form-group" style="display:flex;align-items:center;gap:12px;">
            <label style="margin:0">Active (show to users)</label>
            <input type="checkbox" name="is_active" value="1"
                   {{ old('is_active', $gateway ? ($gateway->is_active ? '1' : '') : '1') ? 'checked' : '' }}
                   style="width:18px;height:18px;">
        </div>

        {{-- Sort Order --}}
        <div class="form-group">
            <label>Sort Order</label>
            <input type="number" name="sort_order" class="form-control" style="width:100px;"
                   value="{{ old('sort_order', $gateway?->sort_order ?? 0) }}">
            <small style="color:#888">Lower number = shown first (when multiple active)</small>
        </div>

        <div style="display:flex;gap:12px;margin-top:20px;">
            <button type="submit" class="btn">{{ $gateway ? 'Update Gateway' : 'Add Gateway' }}</button>
            <a href="{{ route('admin.manual-gateways.index') }}" class="btn" style="background:#6b7280;">Cancel</a>
        </div>
    </form>
</div>

<script>
function toggleFields() {
    const type = document.getElementById('typeSelect').value;
    document.getElementById('upiFields').style.display = type === 'upi' ? '' : 'none';
    document.getElementById('bankFields').style.display = type === 'bank' ? '' : 'none';
}
toggleFields();
</script>
@endsection
