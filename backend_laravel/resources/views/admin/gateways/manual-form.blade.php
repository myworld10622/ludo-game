@extends('admin.layouts.app')

@section('title', $mode === 'edit' ? 'Edit Manual Gateway' : 'Add Manual Gateway')
@section('heading', $mode === 'edit' ? 'Edit Manual Gateway' : 'Add Manual Gateway')
@section('subheading', 'Define gateway rules and charges')

@section('content')
<div class="panel">
    <form method="POST" action="{{ $mode === 'edit' ? route('admin.gateways.manual.update', $gateway->id ?? 0) : route('admin.gateways.manual.store') }}">
        @csrf
        @if ($mode === 'edit')
            @method('PUT')
        @endif

        <div class="split-2">
            <div>
                <label>Gateway Name</label>
                <input type="text" name="name" value="{{ old('name', $gateway->name ?? '') }}" required>
            </div>
            <div>
                <label>Currency</label>
                <input type="text" name="currency" value="{{ old('currency', $gateway->currency ?? '') }}" required>
            </div>
            <div>
                <label>Rate</label>
                <input type="number" step="0.01" name="rate" value="{{ old('rate', $gateway->rate ?? '') }}" required>
            </div>
            <div>
                <label>Role Access</label>
                <select name="role[]" multiple required>
                    @php($selected = old('role', $gateway->role ?? []))
                    <option value="1" {{ in_array('1', $selected, true) ? 'selected' : '' }}>Admin</option>
                    <option value="2" {{ in_array('2', $selected, true) ? 'selected' : '' }}>Agent</option>
                    <option value="3" {{ in_array('3', $selected, true) ? 'selected' : '' }}>Distributor</option>
                </select>
            </div>
            <div>
                <label>Minimum Amount</label>
                <input type="number" step="0.01" name="min_amount" value="{{ old('min_amount', $gateway->min_amount ?? '') }}" required>
            </div>
            <div>
                <label>Maximum Amount</label>
                <input type="number" step="0.01" name="max_amount" value="{{ old('max_amount', $gateway->max_amount ?? '') }}" required>
            </div>
            <div>
                <label>Fixed Charge</label>
                <input type="number" step="0.01" name="fixed_charge" value="{{ old('fixed_charge', $gateway->fixed_charge ?? '') }}" required>
            </div>
            <div>
                <label>Percent Charge (%)</label>
                <input type="number" step="0.01" name="percent_charge" value="{{ old('percent_charge', $gateway->percent_charge ?? '') }}" required>
            </div>
        </div>

        <div style="margin-top: 16px;">
            <label>Deposit Instructions</label>
            <textarea name="instructions" rows="5" required>{{ old('instructions', $gateway->instructions ?? '') }}</textarea>
        </div>

        <div class="mobile-actions" style="margin-top: 16px;">
            <button class="btn" type="submit">{{ $mode === 'edit' ? 'Update' : 'Create' }}</button>
            <a class="btn btn-secondary" href="{{ route('admin.gateways.manual.index') }}">Cancel</a>
        </div>
    </form>
</div>
@endsection
